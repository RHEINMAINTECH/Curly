<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('head'); ?>
<style>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}
.media-item {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 0.375rem;
    cursor: pointer;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}
.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-item .overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}
.media-item:hover .overlay {
    opacity: 1;
}
.upload-zone {
    border: 2px dashed #dee2e6;
    padding: 3rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-zone:hover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.05);
}
.upload-zone.dragover {
    border-color: var(--bs-success);
    background: rgba(var(--bs-success-rgb), 0.1);
}
</style>
<?php $__view->stop(); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Media Library</h1>
    </div>
    
    <!-- Upload Zone -->
    <div class="card mb-4">
        <div class="card-body">
            <div id="uploadZone" class="upload-zone">
                <i class="bi bi-cloud-upload fs-1 text-primary"></i>
                <h5 class="mt-2">Drag & Drop Files Here</h5>
                <p class="text-muted">or click to browse</p>
                <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx" class="d-none">
            </div>
            <div id="uploadProgress" class="mt-3 d-none">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search media..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                        <option value="document" <?= $type === 'document' ? 'selected' : '' ?>>Documents</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Media Grid -->
    <div class="card">
        <div class="card-body">
            <?php if (!empty($media)): ?>
            <div class="media-grid">
                <?php foreach ($media as $item): ?>
                <div class="media-item" 
                     data-id="<?= $item['id'] ?>"
                     data-url="<?= htmlspecialchars($item['path']) ?>"
                     onclick="selectMedia(this)">
                    <?php if ($item['type'] === 'image'): ?>
                    <img src="<?= htmlspecialchars($item['path']) ?>" 
                         alt="<?= htmlspecialchars($item['alt_text'] ?? '') ?>">
                    <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <i class="bi bi-file-earmark fs-1"></i>
                    </div>
                    <?php endif; ?>
                    <div class="overlay">
                        <div class="text-center text-white">
                            <i class="bi bi-eye fs-4"></i>
                            <p class="mb-0 small"><?= htmlspecialchars($item['filename']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-images fs-1"></i>
                <p class="mt-2">No media files yet. Upload your first file above.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Media Detail Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="mediaPreview" class="text-center mb-3"></div>
                <div class="mb-3">
                    <label class="form-label">URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="mediaUrl" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyUrl()">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="deleteMedia()">Delete</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?= $__view->csrf() ?>

<script>
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const uploadProgress = document.getElementById('uploadProgress');

uploadZone.addEventListener('click', () => fileInput.click());
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});
uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});
uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    uploadFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', () => uploadFiles(fileInput.files));

function uploadFiles(files) {
    for (const file of files) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        uploadProgress.classList.remove('d-none');
        
        fetch('/admin/media/upload', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  location.reload();
              } else {
                  alert(data.error || 'Upload failed');
              }
          }).catch(err => {
              alert('Upload error: ' + err.message);
          });
    }
}

let currentMediaId = null;

function selectMedia(el) {
    currentMediaId = el.dataset.id;
    const url = el.dataset.url;
    
    document.getElementById('mediaUrl').value = window.location.origin + url;
    
    if (el.querySelector('img')) {
        document.getElementById('mediaPreview').innerHTML = 
            '<img src="' + url + '" class="img-fluid" alt="">';
    } else {
        document.getElementById('mediaPreview').innerHTML = 
            '<i class="bi bi-file-earmark fs-1"></i>';
    }
    
    new bootstrap.Modal(document.getElementById('mediaModal')).show();
}

function copyUrl() {
    const input = document.getElementById('mediaUrl');
    input.select();
    document.execCommand('copy');
}

function deleteMedia() {
    if (confirm('Delete this media file?') && currentMediaId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/media/' + currentMediaId;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('input[name="_token"]').value}">
            <input type="hidden" name="_method" value="DELETE">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php $__view->stop(); ?>
