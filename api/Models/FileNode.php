<?php
require_once __DIR__ . '/../Config/Database.php';

class FileNode {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createNode($user_id, $parent_id, $name, $type, $permission = 'private', $path = null) {
        $stmt = $this->db->prepare("INSERT INTO files (user_id, parent_id, name, type, path, permission) VALUES (?, ?, ?, ?, ?, ?)");
        if($stmt->execute([$user_id, $parent_id, $name, $type, $path, $permission])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getNode($id) {
        $stmt = $this->db->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getNodesByParent($user_id, $parent_id) {
        if ($parent_id === null) {
            $stmt = $this->db->prepare("SELECT * FROM files WHERE user_id = ? AND parent_id IS NULL ORDER BY type DESC, name ASC");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM files WHERE user_id = ? AND parent_id = ? ORDER BY type DESC, name ASC");
            $stmt->execute([$user_id, $parent_id]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPublicNodes() {
        $stmt = $this->db->prepare("SELECT f.*, u.username FROM files f JOIN users u ON f.user_id = u.id WHERE f.permission IN ('public', 'read_only') ORDER BY f.updated_at DESC LIMIT 100");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePermission($id, $user_id, $permission) {
        $stmt = $this->db->prepare("UPDATE files SET permission = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$permission, $id, $user_id]);
    }
    
    public function updateSize($id, $size) {
        $stmt = $this->db->prepare("UPDATE files SET size = ? WHERE id = ?");
        return $stmt->execute([$size, $id]);
    }

    public function deleteNode($id, $user_id) {
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $user_id]);
    }

    public function renameNode($id, $user_id, $new_name) {
        $stmt = $this->db->prepare("UPDATE files SET name = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$new_name, $id, $user_id]);
    }

    public function moveNode($id, $user_id, $new_parent_id) {
        if ($new_parent_id === '') $new_parent_id = null;
        $stmt = $this->db->prepare("UPDATE files SET parent_id = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$new_parent_id, $id, $user_id]);
    }

    public function createFolderHierarchy($user_id, $parent_id, $folder_path) {
        $parts = explode('/', trim($folder_path, '/'));
        $current_parent = $parent_id;

        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            // Check if folder already exists
            if ($current_parent === null) {
                $stmt = $this->db->prepare("SELECT id FROM files WHERE user_id = ? AND parent_id IS NULL AND name = ? AND type = 'folder'");
                $stmt->execute([$user_id, $part]);
            } else {
                $stmt = $this->db->prepare("SELECT id FROM files WHERE user_id = ? AND parent_id = ? AND name = ? AND type = 'folder'");
                $stmt->execute([$user_id, $current_parent, $part]);
            }
            
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $current_parent = $existing['id'];
            } else {
                // Create it
                $current_parent = $this->createNode($user_id, $current_parent, $part, 'folder', 'private');
            }
        }
        return $current_parent;
    }
}
