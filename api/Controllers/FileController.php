<?php
require_once __DIR__ . '/../Models/FileNode.php';

class FileController {
    private $fileModel;
    private $storageBase;

    public function __construct() {
        $this->fileModel = new FileNode();
        $this->storageBase = realpath(__DIR__ . '/../../') . '/storage/';
        if (!file_exists($this->storageBase)) {
            mkdir($this->storageBase, 0777, true);
        }
    }

    private function getUserId() {
        if(session_status() == PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    public function listNodes($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];

        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? $data['parent_id'] : null;
        $nodes = $this->fileModel->getNodesByParent($userId, $parentId);
        
        $path = [];
        $curr = $parentId;
        while($curr) {
            $node = $this->fileModel->getNode($curr);
            if($node) {
                array_unshift($path, ['id' => $node['id'], 'name' => $node['name']]);
                $curr = $node['parent_id'];
            } else {
                break;
            }
        }

        return ['success' => true, 'nodes' => $nodes, 'path' => $path];
    }
    
    public function publicNodes() {
        $nodes = $this->fileModel->getPublicNodes();
        return ['success' => true, 'nodes' => $nodes];
    }

    public function createNode($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];

        $parentId = isset($data['parent_id']) && $data['parent_id'] !== '' ? $data['parent_id'] : null;
        $name = $data['name'];
        $type = $data['type']; 
        $permission = isset($data['permission']) ? $data['permission'] : 'private';

        $id = $this->fileModel->createNode($userId, $parentId, $name, $type, $permission);
        if($id) {
            if($type === 'file') {
                $userDir = $this->storageBase . $userId . '/';
                if (!file_exists($userDir)) mkdir($userDir, 0777, true);
                
                $physPath = $userDir . $id . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $name);
                file_put_contents($physPath, ""); 
                
                $db = Database::getInstance();
                $stmt = $db->prepare("UPDATE files SET path = ? WHERE id = ?");
                $stmt->execute([$physPath, $id]);
            }
            return ['success' => true, 'node' => $this->fileModel->getNode($id)];
        }
        return ['success' => false, 'message' => 'Failed to create node'];
    }

    public function readFile($data) {
        $id = $data['id'];
        $node = $this->fileModel->getNode($id);
        if(!$node) return ['success' => false, 'message' => 'Not found'];

        $userId = $this->getUserId();
        if($node['user_id'] != $userId && $node['permission'] == 'private') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if($node['type'] !== 'file') return ['success' => false, 'message' => 'Not a file'];

        $mime = file_exists($node['path']) ? mime_content_type($node['path']) : 'text/plain';
        $isImage = strpos($mime, 'image/') === 0;

        if ($isImage) {
            $content = file_exists($node['path']) ? base64_encode(file_get_contents($node['path'])) : '';
        } else {
            $content = file_exists($node['path']) ? file_get_contents($node['path']) : '';
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
            }
        }

        return ['success' => true, 'content' => $content, 'isImage' => $isImage, 'mime' => $mime, 'node' => $node, 'readonly' => ($node['user_id'] != $userId && $node['permission'] == 'read_only')];
    }

    public function saveFile($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];

        $id = $data['id'];
        $content = $data['content'];

        $node = $this->fileModel->getNode($id);
        if(!$node) return ['success' => false, 'message' => 'Not found'];
        
        if($node['user_id'] != $userId && $node['permission'] == 'public') {
            // public can edit
        } else if ($node['user_id'] != $userId) {
            return ['success' => false, 'message' => 'Unauthorized to edit'];
        }

        if($node['type'] !== 'file') return ['success' => false, 'message' => 'Not a file'];

        file_put_contents($node['path'], $content);
        $this->fileModel->updateSize($id, strlen($content));

        return ['success' => true];
    }

    public function updatePermission($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];
        
        $id = $data['id'];
        $permission = $data['permission'];

        if($this->fileModel->updatePermission($id, $userId, $permission)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to update'];
    }
    
    public function deleteNode($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];
        
        $id = $data['id'];
        
        $node = $this->fileModel->getNode($id);
        if($node && $node['user_id'] == $userId) {
            if($node['type'] == 'file' && file_exists($node['path'])) {
                unlink($node['path']);
            }
            $this->fileModel->deleteNode($id, $userId);
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to delete'];
    }

    public function rename($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];
        
        $id = $data['id'];
        $newName = $data['name'];
        if(empty($newName)) return ['success' => false, 'message' => 'Invalid name'];
        
        if($this->fileModel->renameNode($id, $userId, $newName)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to rename'];
    }

    public function move($data) {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];
        
        $id = $data['id'];
        $newParentId = isset($data['parent_id']) ? $data['parent_id'] : null;
        if($id == $newParentId) return ['success' => false, 'message' => 'Cannot move into itself'];
        
        if($this->fileModel->moveNode($id, $userId, $newParentId)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to move'];
    }

    public function upload() {
        $userId = $this->getUserId();
        if(!$userId) return ['success' => false, 'message' => 'Unauthorized'];

        if(!isset($_FILES['file'])) return ['success' => false, 'message' => 'No file uploaded'];

        $file = $_FILES['file'];
        $relativePath = isset($_POST['relativePath']) ? $_POST['relativePath'] : '';
        $baseParentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' && $_POST['parent_id'] !== 'null' ? $_POST['parent_id'] : null;

        $parentId = $baseParentId;
        if(!empty($relativePath)) {
            $pathParts = explode('/', $relativePath);
            array_pop($pathParts); 
            if(count($pathParts) > 0) {
                $folderPath = implode('/', $pathParts);
                $parentId = $this->fileModel->createFolderHierarchy($userId, $baseParentId, $folderPath);
            }
        }

        $name = basename($file['name']);
        
        $id = $this->fileModel->createNode($userId, $parentId, $name, 'file', 'private');
        if($id) {
            $userDir = $this->storageBase . $userId . '/';
            if (!file_exists($userDir)) mkdir($userDir, 0777, true);
            
            $physPath = $userDir . $id . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $name);
            if(move_uploaded_file($file['tmp_name'], $physPath)) {
                $db = Database::getInstance();
                $stmt = $db->prepare("UPDATE files SET path = ?, size = ? WHERE id = ?");
                $stmt->execute([$physPath, $file['size'], $id]);
                return ['success' => true, 'node' => $this->fileModel->getNode($id)];
            } else {
                $this->fileModel->deleteNode($id, $userId);
            }
        }
        return ['success' => false, 'message' => 'Failed to upload'];
    }

    public function download($id) {
        $node = $this->fileModel->getNode($id);
        if(!$node) { header("HTTP/1.0 404 Not Found"); exit; }

        $userId = $this->getUserId();
        if($node['user_id'] != $userId && $node['permission'] == 'private') {
            header("HTTP/1.0 403 Forbidden"); exit;
        }

        if($node['type'] == 'file') {
            if(file_exists($node['path'])) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($node['name']).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($node['path']));
                readfile($node['path']);
                exit;
            }
        } else if ($node['type'] == 'folder') {
            $zipFile = sys_get_temp_dir() . '/' . $node['name'] . '_' . time() . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $this->addFolderToZip($node['id'], $zip, $node['name']);
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.$node['name'].'.zip"');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                unlink($zipFile);
                exit;
            }
        }
        header("HTTP/1.0 404 Not Found"); exit;
    }

    private function addFolderToZip($parentId, $zip, $zipPath) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM files WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $zip->addEmptyDir($zipPath);

        foreach($children as $child) {
            if($child['type'] == 'file') {
                if(file_exists($child['path'])) {
                    $zip->addFile($child['path'], $zipPath . '/' . $child['name']);
                }
            } else {
                $this->addFolderToZip($child['id'], $zip, $zipPath . '/' . $child['name']);
            }
        }
    }
}
