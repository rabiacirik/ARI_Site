<?php
session_start();
include 'baglan.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'];

    if ($form_type === 'login') {
        $eposta = $_POST['eposta'] ?? '';
        $sifre = $_POST['sifre'] ?? '';
        $giris_tipi = $_POST['giris_tipi'] ?? 'kullanici'; // Kullanıcı mı admin mi?

        if ($eposta && $sifre) {
            $stmt = $baglanti->prepare("SELECT id, ad_soyad, sifre, yetki FROM kullanicilar WHERE eposta = ?");
            $stmt->bind_param("s", $eposta);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $kullanici = $result->fetch_assoc();
                if (password_verify($sifre, $kullanici['sifre'])) {
                    if ($giris_tipi === 'admin') {
                        // Admin giriş için yetki kontrolü
                        if ($kullanici['yetki'] == 9) {
                            $_SESSION['kullanici_id'] = $kullanici['id'];
                            $_SESSION['kullanici_adi'] = $kullanici['ad_soyad'];
                            $_SESSION['yetki'] = $kullanici['yetki'];
                            header("Location: admin.php");
                            exit;
                        } else {
                            $error = "Admin yetkiniz yok.";
                        }
                    } else {
                        // Normal kullanıcı girişi
                        $_SESSION['kullanici_id'] = $kullanici['id'];
                        $_SESSION['kullanici_adi'] = $kullanici['ad_soyad'];
                        $_SESSION['yetki'] = $kullanici['yetki'];
                        header("Location: anasayfa.php");
                        exit;
                    }
                } else {
                    $error = "Şifre yanlış.";
                }
            } else {
                $error = "Kullanıcı bulunamadı.";
            }
            $stmt->close();
        } else {
            $error = "Lütfen tüm alanları doldurun.";
        }
    } elseif ($form_type === 'register') {
        $ad_soyad = $_POST['ad_soyad'] ?? '';
        $eposta = $_POST['eposta'] ?? '';
        $sifre_plain = $_POST['sifre'] ?? '';

        if ($ad_soyad && $eposta && $sifre_plain) {
            $sifre_hashed = password_hash($sifre_plain, PASSWORD_DEFAULT);
            $stmt = $baglanti->prepare("INSERT INTO kullanicilar (ad_soyad, eposta, sifre, yetki) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $ad_soyad, $eposta, $sifre_hashed);

            if ($stmt->execute()) {
                $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
            } else {
                $error = "Kayıt başarısız. Lütfen tekrar deneyin.";
            }
            $stmt->close();
        } else {
            $error = "Tüm alanları doldurmanız gerekiyor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş & Kayıt - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/giris.css">
</head>
<body>
<div class="bee-box">
    <h2>🐝 ArıKitap</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" id="formTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Giriş Yap</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Kayıt Ol</button>
        </li>
    </ul>

    <div class="tab-content" id="formTabContent">
        <!-- Giriş -->
        <div class="tab-pane fade show active" id="login" role="tabpanel">
            <form method="POST">
                <input type="hidden" name="form_type" value="login">

                <div class="mb-3">
                    <label class="form-label">E-posta:</label>
                    <input type="email" name="eposta" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Şifre:</label>
                    <input type="password" name="sifre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Giriş Tipi:</label>
                    <select name="giris_tipi" class="form-select" required>
                        <option value="kullanici" selected>Kullanıcı</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-bee w-100">Giriş Yap</button>
            </form>
        </div>

        <!-- Kayıt -->
        <div class="tab-pane fade" id="register" role="tabpanel">
            <form method="POST">
                <input type="hidden" name="form_type" value="register">

                <div class="mb-3">
                    <label class="form-label">Ad Soyad:</label>
                    <input type="text" name="ad_soyad" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-posta:</label>
                    <input type="email" name="eposta" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Şifre:</label>
                    <input type="password" name="sifre" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-bee w-100">Kayıt Ol</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>