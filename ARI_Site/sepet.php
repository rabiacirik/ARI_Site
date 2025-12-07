<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

$kullanici_id = $_SESSION['kullanici_id'];

// Sepetten silme işlemi:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_sepet_id'])) {
    $sil_sepet_id = intval($_POST['sil_sepet_id']);

    // Kullanıcının sepetine ait bu id olduğundan emin ol
    $kontrol = $baglanti->prepare("SELECT * FROM sepet WHERE id = ? AND kullanici_id = ?");
    $kontrol->bind_param("ii", $sil_sepet_id, $kullanici_id);
    $kontrol->execute();
    $sonuc_kontrol = $kontrol->get_result();

    if ($sonuc_kontrol->num_rows > 0) {
        $sil = $baglanti->prepare("DELETE FROM sepet WHERE id = ?");
        $sil->bind_param("i", $sil_sepet_id);
        $sil->execute();
    }
    header("Location: sepet.php"); // Sayfayı yenile
    exit();
}

// Sepeti çek:
$sepet = $baglanti->prepare("SELECT s.*, k.baslik, k.resim_url, k.fiyat FROM sepet s JOIN kitaplar k ON s.kitap_id = k.id WHERE s.kullanici_id = ?");
$sepet->bind_param("i", $kullanici_id);
$sepet->execute();
$sonuc = $sepet->get_result();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Sepetim - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="CSS/sepet.css" />
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1>🐝 Sepetiniz</h1>
    <div class="text-center mt-4">
        <a href="anasayfa.php" class="btn btn-warning">⬅ Ana Sayfaya Dön</a>
    </div>
    <br><br><br>
    <?php if ($sonuc->num_rows > 0): ?>
        <div class="row">
            <?php while ($satir = $sonuc->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <?php if ($satir['resim_url']): ?>
                            <img src="<?= htmlspecialchars($satir['resim_url']) ?>" alt="Kitap Resmi" />
                        <?php else: ?>
                            <img src="default-book.png" alt="Kitap Resmi" />
                        <?php endif; ?>
                        <div class="kitap-baslik"><?= htmlspecialchars($satir['baslik']) ?></div>
                        <div class="kitap-adet">Adet: <?= intval($satir['adet']) ?></div>
                        <div class="kitap-fiyat">Fiyat: <?= number_format($satir['fiyat'], 2, ',', '.') ?> ₺</div>
                        <form method="POST" onsubmit="return confirm('Bu kitabı sepetten silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="sil_sepet_id" value="<?= $satir['id'] ?>" />
                            <button type="submit" class="btn btn-danger mt-3">Sepetten Sil</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <form action="odeme.php" method="post" class="mt-3">
                <button type="submit" class="btn btn-satin-al btn btn-success">💳 Siparişi Tamamla</button>
            </form>
            <br><br>
    <?php else: ?>
        <div class="empty-alert">
            Sepetiniz şu an boş. 🐝 Hadi birkaç kitap ekleyelim!
        </div>
    <?php endif; ?>

    
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
