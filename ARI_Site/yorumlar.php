<?php
session_start();
include("baglan.php"); // Veritabanı bağlantısı

// Kitap ID'sini URL parametresinden alıyoruz
$kitap_id = isset($_GET['kitap_id']) ? $_GET['kitap_id'] : 0;

// Eğer kullanıcı giriş yapmamışsa, yönlendirme yapılabilir
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

// Yorumları çekme
$sql = "SELECT yorumlar.*, kullanicilar.ad_soyad FROM yorumlar 
        JOIN kullanicilar ON yorumlar.kullanici_id = kullanicilar.id
        WHERE yorumlar.kitap_id = ? ORDER BY yorumlar.tarih DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kitap_id);
$stmt->execute();
$result = $stmt->get_result();

// Kitap detaylarını çekme (kitap bilgisi)
$kitap_sql = "SELECT * FROM kitaplar WHERE id = ?";
$kitap_stmt = $conn->prepare($kitap_sql);
$kitap_stmt->bind_param("i", $kitap_id);
$kitap_stmt->execute();
$kitap_result = $kitap_stmt->get_result();
$kitap = $kitap_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $kitap['kitap_adi']; ?> - Yorumlar</title>
</head>
<body>
    <div class="container">
        <h2><?php echo $kitap['kitap_adi']; ?> - Yorumlar</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($yorum = $result->fetch_assoc()): ?>
                <div class="yorum">
                    <strong><?php echo htmlspecialchars($yorum['ad_soyad']); ?>:</strong>
                    <p><?php echo htmlspecialchars($yorum['yorum_metni']); ?></p>
                    <p>Puan: <?php echo $yorum['puan']; ?></p>
                    <small><?php echo $yorum['tarih']; ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Henüz yorum yapılmamış.</p>
        <?php endif; ?>

        <h3>Yorum Yap</h3>
        <form method="POST" action="yorum_ekle.php">
            <textarea name="yorum_metni" placeholder="Yorumunuzu buraya yazın..." required></textarea><br><br>
            <input type="hidden" name="kitap_id" value="<?php echo $kitap_id; ?>"> <label for="puan">Puan:</label>
            <select name="puan" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select><br><br>
            <button type="submit">Yorum Yap</button>
        </form>
    </div>
</body>
</html>