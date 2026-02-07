<?php
require_once '../../includes/session_check.php';

// Security Check: Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    die("Unauthorized access.");
}

require_once '../../config/db_connect.php';

$tables = array();
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sqlScript = "";
foreach ($tables as $table) {
    // Prepare CREATE TABLE statement
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $sqlScript .= "\n\n--\n-- Table structure for table `$table`\n--\n\n";
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlScript .= $row[1] . ";\n\n";

    // Prepare INSERT INTO statements
    $result = $conn->query("SELECT * FROM $table");
    $columnCount = $result->field_count;

    if ($result->num_rows > 0) {
        $sqlScript .= "--\n-- Dumping data for table `$table`\n--\n\n";
        $sqlScript .= "LOCK TABLES `$table` WRITE;\n";
        $sqlScript .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n";
    }

    for ($i = 0; $i < $columnCount; $i++) {
        while ($row = $result->fetch_row()) {
            $sqlScript .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                $row[$j] = $row[$j];

                if (isset($row[$j])) {
                    $sqlScript .= '"' . $conn->real_escape_string($row[$j]) . '"';
                } else {
                    $sqlScript .= '""';
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
    }

    if ($result->num_rows > 0) {
        $sqlScript .= "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n";
        $sqlScript .= "UNLOCK TABLES;\n";
    }
}

$conn->close();

// Download the SQL backup file
$filename = $dbname . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Transfer-Encoding: Binary');
header('Content-disposition: attachment; filename="' . $filename . '"');
echo $sqlScript;
exit;
?>