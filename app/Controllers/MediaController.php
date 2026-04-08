<?php
/**
 * Media Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Helper;
use CurlyCMS\Core\HttpException;

class MediaController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $type = $this->input('type');
        $search = $this->input('search');
        
        $sql = "SELECT * FROM media WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        if ($search) {
            $sql .= " AND (filename LIKE ? OR alt_text LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $media = $this->db->fetchAll($sql, $params);
        
        $this->render('backend.media.index', [
            'media' => $media,
            'type' => $type,
            'search' => $search,
            'title' => 'Media Library'
        ]);
    }

    public function upload(): void
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }
        
        $file = $_FILES['file'];
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['error' => 'File type not allowed'], 400);
            return;
        }
        
        if ($file['size'] > $maxSize) {
            $this->json(['error' => 'File too large (max 10MB)'], 400);
            return;
        }
        
        $uploadDir = CMS_ROOT . '/public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Create date-based subdirectory
        $dateDir = date('Y/m');
        $targetDir = $uploadDir . '/' . $dateDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $filepath = $targetDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->json(['error' => 'Failed to move uploaded file'], 500);
            return;
        }
        
        // Determine media type
        $type = strpos($file['type'], 'image/') === 0 ? 'image' : 'document';
        
        // Get image dimensions
        $width = null;
        $height = null;
        if ($type === 'image' && $file['type'] !== 'image/svg+xml') {
            $imageInfo = getimagesize($filepath);
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
        }
        
        // Save to database
        $mediaId = $this->db->insert('media', [
            'filename' => $filename,
            'original_name' => $file['name'],
            'path' => '/uploads/' . $dateDir . '/' . $filename,
            'type' => $type,
            'mime_type' => $file['type'],
            'size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'alt_text' => $this->input('alt_text', ''),
            'uploaded_by' => $this->session->get('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Generate AI alt text if requested
        $altText = $this->input('alt_text');
        if (empty($altText) && $this->ai && $type === 'image') {
            $generatedAlt = $this->ai->generateAltText(
                $file['name'],
                $this->input('context', '')
            );
            
            if ($generatedAlt) {
                $this->db->update('media', ['alt_text' => $generatedAlt], ['id' => $mediaId]);
            }
        }
        
        $this->json([
            'success' => true,
            'media' => [
                'id' => $mediaId,
                'filename' => $filename,
                'path' => '/uploads/' . $dateDir . '/' . $filename,
                'url' => Helper::url('uploads/' . $dateDir . '/' . $filename),
                'type' => $type,
                'mime_type' => $file['type'],
                'size' => $file['size'],
                'width' => $width,
                'height' => $height
            ]
        ]);
    }

    public function destroy(int $id): void
    {
        $media = $this->db->fetch(
            "SELECT * FROM media WHERE id = ?",
            [$id]
        );
        
        if (!$media) {
            throw new HttpException(404, 'Media not found');
        }
        
        // Delete file
        $filepath = CMS_ROOT . '/public' . $media['path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $this->db->delete('media', ['id' => $id]);
        
        $this->session->flash('success', 'Media deleted successfully.');
        $this->redirect('/admin/media');
    }
}
