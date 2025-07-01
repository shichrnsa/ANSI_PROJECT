
<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "projekklinik");

// === LOGIN ===
if (!isset($_SESSION['login'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $u = $_POST['username'];
        $p = md5($_POST['password']);
        $cek = $koneksi->query("SELECT * FROM users WHERE username='$u' AND password='$p'");
        if ($cek->num_rows > 0) {
            $user = $cek->fetch_assoc();
            $_SESSION['login'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Login gagal. Username atau password salah.";
        }
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Klinik</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <div class="container mt-5" style="max-width:400px">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h4>Login Klinik</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <form method="POST">
                    <input class="form-control mb-2" name="username" placeholder="Username" required>
                    <input class="form-control mb-3" name="password" type="password" placeholder="Password" required>
                    <button class="btn btn-primary w-100" name="login">Login</button>
                </form>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php exit;
}

// === LOGOUT ===
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// === MENU & AKSES ===
$menu = $_GET['menu'] ?? 'pasien';
$id = $_GET['edit'] ?? '';
$data = [];

if ($_SESSION['role'] === 'dokter' && in_array($menu, ['dokter', 'pemeriksaan'])) {
    echo "<div class='alert alert-warning m-3'>Akses ditolak. Hanya admin yang dapat mengakses menu ini.</div>";
    exit;
}

// === GANTI PASSWORD ===
if (isset($_POST['ganti_password'])) {
    $pass1 = $_POST['password_baru'];
    $pass2 = $_POST['konfirmasi'];
    if ($pass1 !== $pass2) {
        $info = "<div class='alert alert-danger mt-2'>Konfirmasi password tidak cocok!</div>";
    } else {
        $hash = md5($pass1);
        $user = $_SESSION['username'];
        $koneksi->query("UPDATE users SET password='$hash' WHERE username='$user'");
        $info = "<div class='alert alert-success mt-2'>Password berhasil diganti.</div>";
    }
}

// === SIMPAN CRUD DATA ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_data'])) {
    $idPost = $_POST['id'] ?? '';
    if ($menu === 'pasien') {
        $nama = $_POST['nama'];
        $alamat = $_POST['alamat'];
        $tgl = $_POST['tgl_lahir'];
        $telp = $_POST['telepon'];
        if ($idPost == '') {
            $koneksi->query("INSERT INTO pasien (nama, alamat, tgl_lahir, telepon) VALUES ('$nama','$alamat','$tgl','$telp')");
        } else {
            $koneksi->query("UPDATE pasien SET nama='$nama', alamat='$alamat', tgl_lahir='$tgl', telepon='$telp' WHERE id_pasien=$idPost");
        }
    }

    if ($menu === 'dokter') {
        $nama = $_POST['nama'];
        $spesialis = $_POST['spesialis'];
        $jadwal = $_POST['jadwal'];
        if ($idPost == '') {
            $koneksi->query("INSERT INTO dokter (nama, spesialis, jadwal_praktek) VALUES ('$nama','$spesialis','$jadwal')");
        } else {
            $koneksi->query("UPDATE dokter SET nama='$nama', spesialis='$spesialis', jadwal_praktek='$jadwal' WHERE id_dokter=$idPost");
        }
    }

    if ($menu === 'pemeriksaan') {
        $id_pasien = $_POST['id_pasien'];
        $id_dokter = $_POST['id_dokter'];
        $diagnosa = $_POST['diagnosa'];
        $resep = $_POST['resep'];
        if ($idPost == '') {
            $koneksi->query("INSERT INTO pemeriksaan (id_pasien, id_dokter, diagnosa, resep) VALUES ('$id_pasien','$id_dokter','$diagnosa','$resep')");
        } else {
            $koneksi->query("UPDATE pemeriksaan SET id_pasien='$id_pasien', id_dokter='$id_dokter', diagnosa='$diagnosa', resep='$resep' WHERE id_pemeriksaan=$idPost");
        }
    }

    header("Location: index.php?menu=$menu");
    exit;
}

// === HAPUS DATA ===
if (isset($_GET['hapus'])) {
    $hapus = $_GET['hapus'];
    if ($menu === 'pasien') $koneksi->query("DELETE FROM pasien WHERE id_pasien = $hapus");
    if ($menu === 'dokter') $koneksi->query("DELETE FROM dokter WHERE id_dokter = $hapus");
    if ($menu === 'pemeriksaan') $koneksi->query("DELETE FROM pemeriksaan WHERE id_pemeriksaan = $hapus");
    header("Location: index.php?menu=$menu");
    exit;
}

// === DATA EDIT ===
if ($id != '') {
    if ($menu == 'pasien') $data = $koneksi->query("SELECT * FROM pasien WHERE id_pasien=$id")->fetch_assoc();
    if ($menu == 'dokter') $data = $koneksi->query("SELECT * FROM dokter WHERE id_dokter=$id")->fetch_assoc();
    if ($menu == 'pemeriksaan') $data = $koneksi->query("SELECT * FROM pemeriksaan WHERE id_pemeriksaan=$id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>sistem informasi klinik by shishi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="text-primary">Sistem Informasi Klinik by Shishi</h4>
        <div>
            <a href="?menu=ganti" class="btn btn-secondary btn-sm">Ganti Password</a>
            <a href="?logout=1" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <nav class="mb-3">
        <a href="?menu=pasien" class="btn btn-outline-primary btn-sm">Pasien</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="?menu=dokter" class="btn btn-outline-primary btn-sm">Dokter</a>
            <a href="?menu=pemeriksaan" class="btn btn-outline-primary btn-sm">Pemeriksaan</a>
        <?php endif; ?>
    </nav>

    <?php if ($menu !== 'ganti'): ?>
    <div class="card mb-3">
        <div class="card-header bg-info text-white">Form <?= ucfirst($menu) ?></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="simpan_data" value="1">
                <?php if ($menu === 'pasien'): ?>
                    <input name="nama" class="form-control mb-2" placeholder="Nama" value="<?= $data['nama'] ?? '' ?>">
                    <input name="alamat" class="form-control mb-2" placeholder="Alamat" value="<?= $data['alamat'] ?? '' ?>">
                    <input name="tgl_lahir" type="date" class="form-control mb-2" value="<?= $data['tgl_lahir'] ?? '' ?>">
                    <input name="telepon" class="form-control mb-2" placeholder="Telepon" value="<?= $data['telepon'] ?? '' ?>">
                <?php elseif ($menu === 'dokter'): ?>
                    <input name="nama" class="form-control mb-2" placeholder="Nama" value="<?= $data['nama'] ?? '' ?>">
                    <input name="spesialis" class="form-control mb-2" placeholder="Spesialis" value="<?= $data['spesialis'] ?? '' ?>">
                    <input name="jadwal" class="form-control mb-2" placeholder="Jadwal Praktek" value="<?= $data['jadwal_praktek'] ?? '' ?>">
                <?php elseif ($menu === 'pemeriksaan'): ?>
                    <select name="id_pasien" class="form-control mb-2">
                        <option value="">Pilih Pasien</option>
                        <?php
                        $res = $koneksi->query("SELECT * FROM pasien");
                        while ($r = $res->fetch_assoc()) {
                            $sel = ($data['id_pasien'] ?? '') == $r['id_pasien'] ? 'selected' : '';
                            echo "<option value='{$r['id_pasien']}' $sel>{$r['nama']}</option>";
                        }
                        ?>
                    </select>
                    <select name="id_dokter" class="form-control mb-2">
                        <option value="">Pilih Dokter</option>
                        <?php
                        $res = $koneksi->query("SELECT * FROM dokter");
                        while ($r = $res->fetch_assoc()) {
                            $sel = ($data['id_dokter'] ?? '') == $r['id_dokter'] ? 'selected' : '';
                            echo "<option value='{$r['id_dokter']}' $sel>{$r['nama']}</option>";
                        }
                        ?>
                    </select>
                    <input name="diagnosa" class="form-control mb-2" placeholder="Diagnosa" value="<?= $data['diagnosa'] ?? '' ?>">
                    <input name="resep" class="form-control mb-2" placeholder="Resep" value="<?= $data['resep'] ?? '' ?>">
                <?php endif; ?>
                <button class="btn btn-success">Simpan</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($info)) echo $info; ?>
</div>
</body>
</html>

<?php if ($menu === 'pasien') {
    $q = $koneksi->query("SELECT * FROM pasien");
    echo "<div class='container mt-4'><h4>Data Pasien</h4><table class='table table-bordered'><thead><tr><th>Nama</th><th>Alamat</th><th>Tgl Lahir</th><th>Telepon</th><th>Aksi</th></tr></thead><tbody>";
    while ($d = $q->fetch_assoc()) {
        echo "<tr>
                <td>{$d['nama']}</td>
                <td>{$d['alamat']}</td>
                <td>{$d['tgl_lahir']}</td>
                <td>{$d['telepon']}</td>
                <td><a href='?menu=pasien&edit={$d['id_pasien']}' class='btn btn-sm btn-warning'>Edit</a></td>
              </tr>";
    }
    echo "</tbody></table></div>";
} ?>

<?php if ($menu === 'dokter') {
    $q = $koneksi->query("SELECT * FROM dokter");
    echo "<div class='container mt-4'><h4>Data Dokter</h4><table class='table table-bordered'><thead><tr><th>Nama</th><th>Spesialis</th><th>Jadwal Praktek</th><th>Aksi</th></tr></thead><tbody>";
    while ($d = $q->fetch_assoc()) {
        echo "<tr>
                <td>{$d['nama']}</td>
                <td>{$d['spesialis']}</td>
                <td>{$d['jadwal_praktek']}</td>
                <td><a href='?menu=dokter&edit={$d['id_dokter']}' class='btn btn-sm btn-warning'>Edit</a></td>
              </tr>";
    }
    echo "</tbody></table></div>";
} ?>

<?php if ($menu === 'pemeriksaan') {
    $q = $koneksi->query("SELECT pemeriksaan.*, pasien.nama AS nama_pasien, dokter.nama AS nama_dokter FROM pemeriksaan 
                          JOIN pasien ON pemeriksaan.id_pasien = pasien.id_pasien 
                          JOIN dokter ON pemeriksaan.id_dokter = dokter.id_dokter");
    echo "<div class='container mt-4'><h4>Data Pemeriksaan</h4><table class='table table-bordered'><thead><tr><th>Pasien</th><th>Dokter</th><th>Diagnosa</th><th>Resep</th><th>Aksi</th></tr></thead><tbody>";
    while ($d = $q->fetch_assoc()) {
        echo "<tr>
                <td>{$d['nama_pasien']}</td>
                <td>{$d['nama_dokter']}</td>
                <td>{$d['diagnosa']}</td>
                <td>{$d['resep']}</td>
                <td><a href='?menu=pemeriksaan&edit={$d['id_pemeriksaan']}' class='btn btn-sm btn-warning'>Edit</a></td>
              </tr>";
    }
    echo "</tbody></table></div>";
} ?>
