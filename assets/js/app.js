const API_BASE = '/antigravityXAMPP/api/';

const app = {
    currentUser: null,
    currentPath: [],
    currentFolderId: null,
    isPublicView: false,
    editorNodeId: null,
    pollInterval: null,
    dragCounter: 0,

    async apiCall(endpoint, payload = null, method = 'POST') {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' }
        };
        if (payload && method === 'POST') {
            options.body = JSON.stringify(payload);
        }
        try {
            const res = await fetch(API_BASE + endpoint, options);
            return await res.json();
        } catch (e) {
            console.error('API Error:', e);
            return { success: false, message: 'Network error' };
        }
    },

    init() {
        this.checkSession();
        // Setup code editor tab support
        const textarea = document.getElementById('code-textarea');
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
                app.updateLineNumbers();
            }
        });

        // Setup drag and drop
        this.setupDragAndDrop();
    },

    setupDragAndDrop() {
        // Prevent default drag behaviors for the entire window
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            window.addEventListener(eventName, (e) => { e.preventDefault(); e.stopPropagation(); }, false);
        });

        const fm = document.getElementById('file-manager');
        const overlay = document.getElementById('drop-overlay');
        
        fm.addEventListener('dragenter', (e) => {
            if(this.isPublicView) return;
            e.preventDefault();
            this.dragCounter++;
            overlay.classList.add('active');
        });

        fm.addEventListener('dragover', (e) => {
            if(this.isPublicView) return;
            e.preventDefault();
        });
        
        overlay.addEventListener('dragleave', (e) => {
            e.preventDefault();
            this.dragCounter--;
            if (this.dragCounter === 0) {
                overlay.classList.remove('active');
            }
        });
        
        overlay.addEventListener('drop', async (e) => {
            e.preventDefault();
            this.dragCounter = 0;
            overlay.classList.remove('active');
            if(this.isPublicView) return;
            
            const items = e.dataTransfer.items;
            if(items) {
                this.startUploadSequence(items);
            }
        });

        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('upload-dropdown');
            if (dropdown && !e.target.closest('#upload-menu-container')) {
                dropdown.classList.remove('show');
            }
        });
    },

    toggleUploadMenu() {
        document.getElementById('upload-dropdown').classList.toggle('show');
    },

    async handleManualUpload(e, isFolder) {
        document.getElementById('upload-dropdown').classList.remove('show');
        const files = e.target.files;
        if (!files.length) return;
        
        const overlay = document.getElementById('drop-overlay');
        const statusEl = document.getElementById('upload-status');
        overlay.classList.add('active');
        statusEl.innerText = 'Uploading files... Please wait.';
        
        const promises = [];
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const relativePath = isFolder && file.webkitRelativePath ? file.webkitRelativePath : file.name;
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('relativePath', relativePath);
            formData.append('parent_id', this.currentFolderId || '');
            
            promises.push(fetch(API_BASE + 'files/upload', {
                method: 'POST',
                body: formData
            }));
        }
        
        await Promise.all(promises);
        
        statusEl.innerText = '';
        overlay.classList.remove('active');
        e.target.value = '';
        this.showToast('Files uploaded successfully', 'success');
        this.loadFiles(this.currentFolderId);
    },

    async startUploadSequence(items) {
        const overlay = document.getElementById('drop-overlay');
        const statusEl = document.getElementById('upload-status');
        overlay.classList.add('active');
        statusEl.innerText = 'Uploading items... Please wait.';
        
        const promises = [];
        for (let i=0; i<items.length; i++) {
            const item = items[i].webkitGetAsEntry();
            if (item) {
                promises.push(this.traverseFileTree(item, ''));
            }
        }
        await Promise.all(promises);
        
        statusEl.innerText = '';
        overlay.classList.remove('active');
        this.showToast('Items uploaded successfully', 'success');
        this.loadFiles(this.currentFolderId);
    },

    async traverseFileTree(item, path) {
        path = path || "";
        if (item.isFile) {
            return new Promise((resolve) => {
                item.file(async (file) => {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('relativePath', path + file.name);
                    formData.append('parent_id', this.currentFolderId || '');
                    
                    try {
                        await fetch(API_BASE + 'files/upload', {
                            method: 'POST',
                            body: formData
                        });
                    } catch(e) { console.error(e); }
                    resolve();
                });
            });
        } else if (item.isDirectory) {
            const dirReader = item.createReader();
            const readEntriesPromise = async () => {
                let allEntries = [];
                let readPromise = () => new Promise((resolve) => dirReader.readEntries(resolve));
                let entries;
                do {
                    entries = await readPromise();
                    allEntries = allEntries.concat(entries);
                } while (entries.length > 0);
                return allEntries;
            };

            const entries = await readEntriesPromise();
            const childPromises = [];
            for (let i=0; i<entries.length; i++) {
                childPromises.push(this.traverseFileTree(entries[i], path + item.name + "/"));
            }
            return Promise.all(childPromises);
        }
    },

    async checkSession() {
        const res = await this.apiCall('auth/session', null, 'GET');
        if (res.success) {
            this.currentUser = res.user;
            document.getElementById('current-username').innerText = res.user.username;
            this.showView('dashboard-view');
            this.loadFiles(null);
            this.startPolling();
        } else {
            this.showView('auth-view');
        }
    },

    showView(viewId) {
        document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
        document.getElementById(viewId).classList.add('active');
    },

    switchAuthTab(tab) {
        document.querySelectorAll('.auth-tabs button').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
        document.getElementById(tab + '-form').classList.add('active');
        document.getElementById('auth-msg').innerText = '';
    },

    async login() {
        const u = document.getElementById('login-user').value;
        const p = document.getElementById('login-pass').value;
        const res = await this.apiCall('auth/login', { username: u, password: p });
        if (res.success) {
            this.checkSession();
        } else {
            document.getElementById('auth-msg').innerText = res.message;
        }
    },

    async register() {
        const u = document.getElementById('reg-user').value;
        const p = document.getElementById('reg-pass').value;
        const res = await this.apiCall('auth/register', { username: u, password: p });
        if (res.success) {
            this.checkSession();
        } else {
            document.getElementById('auth-msg').innerText = res.message;
        }
    },

    async logout() {
        await this.apiCall('auth/logout', null, 'GET');
        this.currentUser = null;
        this.stopPolling();
        this.showView('auth-view');
        document.getElementById('login-user').value = '';
        document.getElementById('login-pass').value = '';
    },

    startPolling() {
        this.pollNodes();
        this.pollInterval = setInterval(() => this.pollNodes(), 30000);
    },

    stopPolling() {
        if (this.pollInterval) clearInterval(this.pollInterval);
    },

    async pollNodes() {
        const res = await this.apiCall('nodes/active', null, 'GET');
        if (res.success) {
            document.getElementById('active-nodes').innerText = res.active_nodes;
        }
    },

    async loadFiles(parentId) {
        this.isPublicView = false;
        this.currentFolderId = parentId;
        document.getElementById('nav-my-files').classList.add('active');
        document.getElementById('nav-network').classList.remove('active');
        document.getElementById('btn-create-folder').classList.remove('hidden');
        document.getElementById('btn-create-file').classList.remove('hidden');
        document.getElementById('btn-download-zip').classList.toggle('hidden', parentId === null);
        
        const res = await this.apiCall('files/list', { parent_id: parentId });
        if (res.success) {
            this.currentPath = res.path;
            this.renderBreadcrumbs();
            this.renderFiles(res.nodes);
        }
    },

    async loadPublicFiles() {
        this.isPublicView = true;
        this.currentFolderId = null;
        this.currentPath = [];
        document.getElementById('nav-network').classList.add('active');
        document.getElementById('nav-my-files').classList.remove('active');
        document.getElementById('btn-create-folder').classList.add('hidden');
        document.getElementById('btn-create-file').classList.add('hidden');
        document.getElementById('btn-download-zip').classList.add('hidden');

        const res = await this.apiCall('files/public', null, 'GET');
        if (res.success) {
            this.renderBreadcrumbs('Network Public Files');
            this.renderFiles(res.nodes);
        }
    },

    renderBreadcrumbs(customText = null) {
        const container = document.getElementById('breadcrumbs');
        container.innerHTML = '';
        if (customText) {
            container.innerHTML = `<span>${customText}</span>`;
            return;
        }

        const rootSpan = document.createElement('span');
        rootSpan.innerText = 'Root';
        rootSpan.onclick = () => this.loadFiles(null);
        container.appendChild(rootSpan);

        this.currentPath.forEach((p, idx) => {
            const sep = document.createElement('span');
            sep.className = 'separator';
            sep.innerText = ' / ';
            container.appendChild(sep);

            const pSpan = document.createElement('span');
            pSpan.innerText = p.name;
            if (idx < this.currentPath.length - 1) {
                pSpan.onclick = () => this.loadFiles(p.id);
            }
            container.appendChild(pSpan);
        });
    },

    renderFiles(nodes) {
        const grid = document.getElementById('file-grid');
        grid.innerHTML = '';
        if (nodes.length === 0) {
            grid.innerHTML = '<div style="color:var(--text-muted); grid-column: 1/-1; text-align:center;">No files found.</div>';
            return;
        }

        nodes.forEach(n => {
            const item = document.createElement('div');
            item.className = 'file-item';
            
            const icon = n.type === 'folder' ? '📁' : '📄';
            let ownerBadge = this.isPublicView ? `<div class="file-owner">@${n.username}</div>` : '';
            let permIcon = '';
            if(!this.isPublicView) {
                if(n.permission === 'public') permIcon = '🌍 ';
                if(n.permission === 'read_only') permIcon = '👁️ ';
            }
            
            let actionsMenu = '';
            if(!this.isPublicView) {
                actionsMenu = `
                <div class="file-actions-menu">
                    <button onclick="app.showRenameModal(event, ${n.id}, '${n.name.replace(/'/g, "\\'")}')">✎</button>
                    <button onclick="app.showMoveModal(event, ${n.id})">➡</button>
                    <button onclick="app.directDownload(event, ${n.id})">⬇</button>
                    <button style="background: var(--danger)" onclick="app.deleteItem(event, ${n.id})">❌</button>
                </div>`;
            }

            const sizeDisplay = n.type === 'folder' ? 'Folder' : this.formatBytes(n.size);

            item.innerHTML = `
                ${actionsMenu}
                ${ownerBadge}
                <div class="file-icon">${icon}</div>
                <div class="file-name">${permIcon}${n.name}</div>
                <div class="file-meta">${sizeDisplay}</div>
            `;

            item.onclick = () => {
                if (n.type === 'folder') {
                    if(!this.isPublicView) this.loadFiles(n.id);
                } else {
                    this.openFile(n.id);
                }
            };
            grid.appendChild(item);
        });
    },

    showCreateModal(type) {
        document.getElementById('create-type').value = type;
        document.getElementById('modal-title').innerText = `Create ${type === 'folder' ? 'Folder' : 'File'}`;
        document.getElementById('create-name').value = '';
        document.getElementById('create-modal').classList.add('active');
        document.getElementById('create-name').focus();
    },

    closeModal() {
        document.getElementById('create-modal').classList.remove('active');
    },

    async createItem() {
        const type = document.getElementById('create-type').value;
        const name = document.getElementById('create-name').value.trim();
        if(!name) return;

        const res = await this.apiCall('files/create', {
            parent_id: this.currentFolderId,
            name: name,
            type: type
        });

        if (res.success) {
            this.closeModal();
            this.showToast(`${type === 'folder' ? 'Folder' : 'File'} created successfully`, 'success');
            this.loadFiles(this.currentFolderId);
        } else {
            this.showToast(res.message, 'error');
        }
    },
    
    showRenameModal(e, id, name) {
        e.stopPropagation();
        document.getElementById('rename-id').value = id;
        document.getElementById('rename-name').value = name;
        document.getElementById('rename-modal').classList.add('active');
    },
    closeRenameModal() { document.getElementById('rename-modal').classList.remove('active'); },
    async submitRename() {
        const id = document.getElementById('rename-id').value;
        const name = document.getElementById('rename-name').value.trim();
        if(!name) return;
        const res = await this.apiCall('files/rename', { id, name });
        if(res.success) {
            this.closeRenameModal();
            this.showToast('Item renamed successfully', 'success');
            this.loadFiles(this.currentFolderId);
        } else {
            this.showToast(res.message, 'error');
        }
    },

    async showMoveModal(e, id) {
        e.stopPropagation();
        const res = await this.apiCall('files/list', { parent_id: null });
        const dest = document.getElementById('move-dest');
        dest.innerHTML = '<option value="">Root</option>';
        if(res.success) {
            res.nodes.filter(n => n.type === 'folder' && n.id != id).forEach(n => {
                dest.innerHTML += `<option value="${n.id}">${n.name}</option>`;
            });
        }
        
        document.getElementById('move-id').value = id;
        document.getElementById('move-modal').classList.add('active');
    },
    closeMoveModal() { document.getElementById('move-modal').classList.remove('active'); },
    async submitMove() {
        const id = document.getElementById('move-id').value;
        const parentId = document.getElementById('move-dest').value;
        const res = await this.apiCall('files/move', { id, parent_id: parentId });
        if(res.success) {
            this.closeMoveModal();
            this.showToast('Item moved successfully', 'success');
            this.loadFiles(this.currentFolderId);
        } else {
            this.showToast(res.message, 'error');
        }
    },
    
    async deleteItem(e, id) {
        e.stopPropagation();
        if(!confirm('Delete this item permanently?')) return;
        const res = await this.apiCall('files/delete', { id: id });
        if(res.success) {
            this.showToast('Item deleted', 'success');
            this.loadFiles(this.currentFolderId);
        } else {
            this.showToast(res.message, 'error');
        }
    },

    async openFile(id) {
        const res = await this.apiCall('files/read', { id: id });
        if (res.success) {
            this.editorNodeId = id;
            document.getElementById('editor-filename').innerText = res.node.name;
            
            const ta = document.getElementById('code-textarea');
            const numCont = document.getElementById('line-numbers');
            
            let imgViewer = document.getElementById('image-viewer');
            if (!imgViewer) {
                imgViewer = document.createElement('img');
                imgViewer.id = 'image-viewer';
                imgViewer.style.maxWidth = '100%';
                imgViewer.style.maxHeight = '100%';
                imgViewer.style.objectFit = 'contain';
                imgViewer.style.padding = '24px';
                imgViewer.style.margin = 'auto';
                imgViewer.style.display = 'none';
                document.getElementById('editor-container').appendChild(imgViewer);
            }

            if (res.isImage) {
                ta.style.display = 'none';
                numCont.style.display = 'none';
                imgViewer.style.display = 'block';
                imgViewer.src = `data:${res.mime};base64,${res.content}`;
                document.getElementById('btn-save').classList.add('hidden');
            } else {
                imgViewer.style.display = 'none';
                ta.style.display = 'block';
                numCont.style.display = 'block';
                ta.value = res.content;
                ta.readOnly = res.readonly;
                document.getElementById('btn-save').classList.toggle('hidden', res.readonly);
                this.updateLineNumbers();
            }
            
            const permSelect = document.getElementById('editor-permission');
            if(res.node.user_id == this.currentUser.id) {
                permSelect.value = res.node.permission;
                permSelect.classList.remove('hidden');
            } else {
                permSelect.classList.add('hidden');
            }

            document.querySelectorAll('.manager-view').forEach(v => v.classList.remove('active'));
            document.getElementById('editor-view').classList.add('active');
            
        } else {
            this.showToast(res.message, 'error');
        }
    },

    closeEditor() {
        this.editorNodeId = null;
        document.querySelectorAll('.manager-view').forEach(v => v.classList.remove('active'));
        document.getElementById('file-manager').classList.add('active');
    },

    async saveFile() {
        if (!this.editorNodeId) return;
        const content = document.getElementById('code-textarea').value;
        const res = await this.apiCall('files/save', { id: this.editorNodeId, content: content });
        if (res.success) {
            this.showToast('File saved successfully', 'success');
        } else {
            this.showToast(res.message, 'error');
        }
    },

    async changePermission(val) {
        if (!this.editorNodeId) return;
        const res = await this.apiCall('files/permission', { id: this.editorNodeId, permission: val });
        if (res.success) {
            this.showToast('Permission updated successfully', 'success');
        }
    },

    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '❌';
        
        toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    formatBytes(bytes, decimals = 2) {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    },

    filterFiles() {
        const query = document.getElementById('file-search').value.toLowerCase();
        const items = document.querySelectorAll('.file-item');
        items.forEach(item => {
            const name = item.querySelector('.file-name').innerText.toLowerCase();
            if (name.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    },

    downloadFile() {
        if (this.editorNodeId) {
            window.location.href = API_BASE + 'files/download?id=' + this.editorNodeId;
        }
    },
    
    directDownload(e, id) {
        e.stopPropagation();
        window.location.href = API_BASE + 'files/download?id=' + id;
    },
    
    downloadCurrentFolder() {
        if (this.currentFolderId) {
            window.location.href = API_BASE + 'files/download?id=' + this.currentFolderId;
        }
    },

    updateLineNumbers() {
        const ta = document.getElementById('code-textarea');
        const lines = ta.value.split('\n').length;
        const numbersContainer = document.getElementById('line-numbers');
        numbersContainer.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
    },

    syncScroll() {
        const ta = document.getElementById('code-textarea');
        const numbersContainer = document.getElementById('line-numbers');
        numbersContainer.scrollTop = ta.scrollTop;
    }
};

window.onload = () => app.init();
