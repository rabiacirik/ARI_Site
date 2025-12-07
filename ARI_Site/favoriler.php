<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = $_SESSION['kullanici_id'];

// Sepete ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sepete_ekle_kitap_id'])) {
    $sepete_ekle_kitap_id = intval($_POST['sepete_ekle_kitap_id']);
    $adet = 1; // varsayılan adet

    // Sepette var mı kontrol
    $kontrolSepet = $baglanti->prepare("SELECT * FROM sepet WHERE kullanici_id = ? AND kitap_id = ?");
    $kontrolSepet->bind_param("ii", $kullanici_id, $sepete_ekle_kitap_id);
    $kontrolSepet->execute();
    $sonucSepet = $kontrolSepet->get_result();

    if ($sonucSepet->num_rows > 0) {
        // Varsa adet artır
        $guncelleSepet = $baglanti->prepare("UPDATE sepet SET adet = adet + ? WHERE kullanici_id = ? AND kitap_id = ?");
        $guncelleSepet->bind_param("iii", $adet, $kullanici_id, $sepete_ekle_kitap_id);
        $guncelleSepet->execute();
        $guncelleSepet->close();
    } else {
        // Yoksa yeni kayıt ekle
        $ekleSepet = $baglanti->prepare("INSERT INTO sepet (kullanici_id, kitap_id, adet) VALUES (?, ?, ?)");
        $ekleSepet->bind_param("iii", $kullanici_id, $sepete_ekle_kitap_id, $adet);
        $ekleSepet->execute();
        $ekleSepet->close();
    }
    $kontrolSepet->close();

    header("Location: favoriler.php?sepete_eklendi=1");
    exit();
}

// Favorilerden kaldırma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_favori_id'])) {
    $sil_favori_id = intval($_POST['sil_favori_id']);

    $kontrol = $baglanti->prepare("SELECT * FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
    $kontrol->bind_param("ii", $kullanici_id, $sil_favori_id);
    $kontrol->execute();
    $sonuc_kontrol = $kontrol->get_result();

    if ($sonuc_kontrol->num_rows > 0) {
        $sil = $baglanti->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND kitap_id = ?");
        $sil->bind_param("ii", $kullanici_id, $sil_favori_id);
        $sil->execute();
    }
    header("Location: favoriler.php");
    exit();
}

// Favorideki kitaplar sorgusu
$favoriler = $baglanti->prepare("SELECT kitaplar.id, kitaplar.baslik, kitaplar.resim_url, kitaplar.fiyat, kitaplar.ozet, kategoriler.ad AS kategori_adi
                                 FROM favoriler 
                                 INNER JOIN kitaplar ON favoriler.kitap_id = kitaplar.id
                                 INNER JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id
                                 WHERE favoriler.kullanici_id = ?
                                 ORDER BY kitaplar.id DESC");
$favoriler->bind_param("i", $kullanici_id);
$favoriler->execute();
$result = $favoriler->get_result();

$sepete_eklendi_mesaji = isset($_GET['sepete_eklendi']) && $_GET['sepete_eklendi'] == 1;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Favori Kitaplar - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="CSS/favoriler.css" />
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h1>🐝 Favori Kitaplarınız</h1>

    <?php if ($sepete_eklendi_mesaji): ?>
        <div class="alert alert-success text-center" role="alert">
            🛒 Kitap sepete eklendi!
        </div>
    <?php endif; ?>

    <div class="text-center mb-4">
        <a href="anasayfa.php" class="btn btn-warning">Anasayfaya Dön</a>
    </div>

    <?php if ($result->num_rows == 0): ?>
        <p class="text-center fs-5"> Favorileriniz şu an boş. 🐝 Hadi birkaç kitap ekleyelim!</p><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <?php else: ?>
        <div class="row">
            <?php while ($kitap = $result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <?php if ($kitap['resim_url']): ?>
                            <img src="<?= htmlspecialchars($kitap['resim_url']) ?>" alt="Kitap Resmi" class="img-fluid mb-3">
                        <?php endif; ?>
                        <h5><?= htmlspecialchars($kitap['baslik']) ?></h5>
                        <div class="kategori-badge"><?= htmlspecialchars($kitap['kategori_adi']) ?></div>
                        <p><strong>Fiyat:</strong> <?= htmlspecialchars($kitap['fiyat']) ?> ₺</p>

                        <form method="POST" class="mb-2">
                            <input type="hidden" name="sepete_ekle_kitap_id" value="<?= $kitap['id'] ?>" />
                            <button type="submit" class="btn btn-warning w-100">Sepete Ekle</button>
                        </form>

                        <form method="POST" onsubmit="return confirm('Bu kitabı favorilerden kaldırmak istediğinize emin misiniz?');">
                            <input type="hidden" name="sil_favori_id" value="<?= $kitap['id'] ?>" />
                            <button type="submit" class="btn btn-danger w-100">Favorilerden Kaldır</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
    <footer><?php include 'footer.php'; ?></footer>
    

</div>
