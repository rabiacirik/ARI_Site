<?php
session_start();
include 'baglan.php';

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

$kullanici_id = $_SESSION['kullanici_id'];
$adres = $_POST['adres'];
$tarih = date('Y-m-d H:i:s');

// Sepeti al
$sepet = $baglanti->prepare("SELECT s.*, k.fiyat FROM sepet s JOIN kitaplar k ON s.kitap_id = k.id WHERE s.kullanici_id = ?");
$sepet->bind_param("i", $kullanici_id);
$sepet->execute();
$sonuc = $sepet->get_result();

if ($sonuc->num_rows === 0) {
    echo "Sepet boş, sipariş verilemez.";
    exit();
}

// Her kitap için satislar tablosuna kayıt ekle
while ($satir = $sonuc->fetch_assoc()) {
    $kitap_id = $satir['kitap_id'];
    $adet = $satir['adet'];
    $fiyat = $satir['fiyat'];
    $toplam_tutar = $fiyat * $adet;

    $satis = $baglanti->prepare("INSERT INTO satislar (kullanici_id, kitap_id, adet, adres, toplam_tutar, tarih) VALUES (?, ?, ?, ?, ?, ?)");
    $satis->bind_param("iiisds", $kullanici_id, $kitap_id, $adet, $adres, $toplam_tutar, $tarih);
    $satis->execute();
}

// Sepeti temizle
$temizle = $baglanti->prepare("DELETE FROM sepet WHERE kullanici_id = ?");
$temizle->bind_param("i", $kullanici_id);
$temizle->execute();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teşekkürler - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffbee; font-family: 'Segoe UI', sans-serif; }
        .tesekkur-container { max-width: 600px; margin: 50px auto; background-color: #fff8d6; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-anasayfa { background-color: #ffd000; color: black; font-weight: 500; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container tesekkur-container">
    <h2>🎉 Siparişiniz Alındı!</h2>
    <p>Teşekkür ederiz! 📦 Siparişiniz en kısa sürede adresinize ulaştırılacaktır.</p>
    <a href="anasayfa.php" class="btn btn-anasayfa">🏠 Ana Sayfaya Dön</a>
</div>
<?php include 'footer.php'; ?>
</body>
</html>