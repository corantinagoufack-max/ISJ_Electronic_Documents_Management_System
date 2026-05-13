<?php

//DocumentManager Class Handles the lifecycle of documents in the ISJ-DMS Academic Vault.
class DocumentManager
{
    private $db;
    private $uploadDir = __DIR__ . '/../documents/users/';

    public function __construct($db_connection)
    {
        if (!$db_connection) {
            die("Database connection failed: The variable passed to DocumentManager is empty.");
        }
        $this->db = $db_connection;
    }

    //Initial Document Upload
    // Inside includes/DocumentManager.php

    public function uploadDocument($file, $userId, $categoryId, $title, $description, $status)
    {
        $allowed = ['pdf', 'docx', 'jpg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) return false;

        $safeName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $fileSize = $file['size'];
        $targetPath = $this->uploadDir . $safeName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // FIXED: Column names match your screenshot (uploaded_by, file_path)
            $stmt = $this->db->prepare("
            INSERT INTO documents (uploaded_by, title, file_path, original_name, category_id, description, size, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

            $originalName = $file['name'];
            $stmt->bind_param(
                "isssisis",
                $userId,
                $title,
                $safeName,
                $originalName,
                $categoryId,
                $description,
                $fileSize,
                $status
            );

            return $stmt->execute();
        }
        return false;
    }

    /* Add a New Version (Requirement: Versioning)* 
     * Keeps the original record but stores the new file path and increments the version number.*/

    public function addVersion($documentId, $file, $comment, $userId)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = time() . '_v_update.' . $ext;
        $targetPath = $this->uploadDir . $safeName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // 1. Insert into versions history table
            $stmt = $this->db->prepare("INSERT INTO document_versions (document_id, file_path, comment, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $documentId, $safeName, $comment, $userId);
            $stmt->execute();

            // 2. Update the main document record to point to the latest version
            $update = $this->db->prepare("UPDATE documents SET file_name = ?, version = version + 1 WHERE id = ?");
            $update->bind_param("si", $safeName, $documentId);
            return $update->execute();
        }
        return false;
    }

    //secure Download (Requirement: Permission Check)Verifies user has access before providing the file path.
    public function getDocumentForDownload($documentId, $userId)
    {
        // Only allow if owner, or if document is explicitly shared/public
        $stmt = $this->db->prepare("
            SELECT file_name, original_name FROM documents 
            WHERE id = ? AND (user_id = ? OR status = 'Final')
        ");
        $stmt->bind_param("ii", $documentId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    //Soft Delete (Requirement: Trash/Archiving)

    public function softDelete($documentId)
    {
        $stmt = $this->db->prepare("UPDATE documents SET status = 'Archived' WHERE id = ?");
        $stmt->bind_param("i", $documentId);
        return $stmt->execute();
    }

    //Fetch Documents for Browser
    public function getDocuments($userId = null)
    {
        $sql = "SELECT d.*, c.name as category_name FROM documents d 
                LEFT JOIN categories c ON d.category_id = c.id";
        if ($userId) {
            $sql .= " WHERE d.user_id = " . intval($userId);
        }
        $sql .= " ORDER BY d.updated_at DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}
