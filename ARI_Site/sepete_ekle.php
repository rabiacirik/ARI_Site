<?php
session_start();
include 'baglan.php';

if (isset($_POST['kitap_id'], $_POST['adet'])) {
    $kitap_id = $_POST['kitap_id'];
    $kullanici_id = $_SESSION['kullanici_id'];
    $adet = $_POST['adet'];

    // Sepette zaten var mı?
    $kontrol = $baglanti->prepare("SELECT * FROM sepet WHERE kullanici_id = ? AND kitap_id = ?");
    $kontrol->bind_param("ii", $kullanici_id, $kitap_id);
    $kontrol->execute();
    $sonuc = $kontrol->get_result();

    if ($sonuc->num_rows > 0) {
        // Varsa adeti arttır
        $guncelle = $baglanti->prepare("UPDATE sepet SET adet = adet + ? WHERE kullanici_id = ? AND kitap_id = ?");
        $guncelle->bind_param("iii", $adet, $kullanici_id, $kitap_id);
        $guncelle->execute();
    } else {
        // Yoksa yeni ekle
        $ekle = $baglanti->prepare("INSERT INTO sepet (kullanici_id, kitap_id, adet) VALUES (?, ?, ?)");
        $ekle->bind_param("iii", $kullanici_id, $kitap_id, $adet);
        $ekle->execute();
    }

    // ✅ Başarı mesajı
    $_SESSION['sepet_mesaj'] = "Kitap sepete eklendi!";
}

header("Location: anasayfa.php");
exit;
?>
