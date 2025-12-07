<?php
session_start();
include 'baglan.php';

// İzin verilen kategori ID'leri
$izin_verilen_idler = [1, 2, 3, 4];

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !in_array((int)$_GET['id'], $izin_verilen_idler)) {
    header("Location: anasayfa.php");
    exit();
}

$kategori_id = (int)$_GET['id'];
$kullanici_id = $_SESSION['kullanici_id'] ?? null; // Oturumdan kullanıcı ID'sini al, oturum yoksa null

// Kullanıcının favori kitaplarının id'lerini alıyoruz
$fav_ids = [];
if ($kullanici_id) {
    $fav_sorgu = $baglanti->prepare("SELECT kitap_id FROM favoriler WHERE kullanici_id = ?");
    $fav_sorgu->bind_param("i", $kullanici_id);
    $fav_sorgu->execute();
    $fav_result = $fav_sorgu->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $fav_ids[] = $row['kitap_id'];
    }
    $fav_sorgu->close();
}

// Seçilen kategori bilgisi
$kategori_sorgu = $baglanti->prepare("SELECT ad FROM kategoriler WHERE id = ?");
$kategori_sorgu->bind_param("i", $kategori_id);
$kategori_sorgu->execute();
$kategori_sonuc = $kategori_sorgu->get_result();

if ($kategori_sonuc->num_rows == 0) {
    header("Location: anasayfa.php");
    exit();
}

$kategori = $kategori_sonuc->fetch_assoc();

// Kategoriye ait kitapları getir
$kitaplar = $baglanti->prepare("SELECT kitaplar.id, kitaplar.baslik, kitaplar.resim_url, kitaplar.fiyat, kitaplar.ozet, kategoriler.ad AS kategori_adi
                               FROM kitaplar
                               INNER JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id
                               WHERE kitaplar.kategori_id = ?
                               ORDER BY kitaplar.id DESC");
$kitaplar->bind_param("i", $kategori_id);
$kitaplar->execute();
$kitaplar_sonuc = $kitaplar->get_result();

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($kategori['ad'] ?? 'Kategori Bulunamadı') ?> - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/kategori.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light" style="background-color: #d4af37;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="anasayfa.php" style="color: #1a1a1a;">🐝 ArıKitap</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold" href="#" id="kategoriDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color:#1a1a1a;">
                        Kategoriler
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="kategoriDropdown">
                        <?php
                        $izin_verilen_idler_str = implode(',', [1, 2, 3, 4]);
                        $kategoriler_nav = $baglanti->query("SELECT * FROM kategoriler WHERE id IN ($izin_verilen_idler_str) ORDER BY ad ASC");
                        while ($kategori_nav = $kategoriler_nav->fetch_assoc()):
                        ?>
                            <li><a class="dropdown-item" href="kategori.php?id=<?= $kategori_nav['id'] ?>"><?= htmlspecialchars($kategori_nav['ad']) ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <?php if (isset($_SESSION['kullanici_id'])): ?>
                    <li class="nav-item me-3">
                        <span class="text-dark fw-semibold">Hoşgeldiniz, <?= htmlspecialchars($_SESSION['kullanici_adi'] ?? 'Kullanıcı') ?>!</span>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="favoriler.php">Favoriler</a>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="sepet.php">Sepet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="cikis.php">Çıkış Yap</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2">
                        <a class="nav-link fw-semibold" href="giris.php">Giriş Yap</a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <a href="anasayfa.php" class="btn btn-anasayfa">🏠 Anasayfaya Dön</a>

    <h1>🐝 Kategori: <?= htmlspecialchars($kategori['ad'] ?? 'Kategori Bulunamadı') ?> 🐝</h1>

    <?php if ($kitaplar_sonuc->num_rows > 0): ?>
        <?php while ($kitap = $kitaplar_sonuc->fetch_assoc()):
            $favorited = in_array($kitap['id'], $fav_ids);
        ?>
            <div class="card mb-4 p-3">
                <div class="row g-3 align-items-center position-relative">
                    <?php if (isset($_SESSION['kullanici_id'])): ?>
                        <div class="fav-heart <?= $favorited ? 'favorited' : '' ?>" data-kitap-id="<?= $kitap['id'] ?>" title="Favorilere Ekle/Çıkar">
                            &#10084;
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($kitap['resim_url'])): ?>
                        <div class="col-md-2 text-center">
                            <img src="<?= htmlspecialchars($kitap['resim_url']) ?>" alt="Kitap Resmi" class="kitap-resim">
                        </div>
                    <?php endif; ?>
                    <div class="<?= !empty($kitap['resim_url']) ? 'col-md-10' : 'col-12' ?>">
                        <h5 class="card-title"><?= htmlspecialchars($kitap['baslik']) ?></h5>
                        <p><strong>Kategori:</strong> <?= htmlspecialchars($kitap['kategori_adi']) ?></p>
                        <p><?= htmlspecialchars($kitap['ozet']) ?></p>
                        <p><strong>Fiyat:</strong> <?= htmlspecialchars($kitap['fiyat']) ?> ₺</p>

                        <?php if (isset($_SESSION['kullanici_id'])) : ?>
                            <form action="sepete_ekle.php" method="POST" class="mt-2">
                                <input type="hidden" name="kitap_id" value="<?= $kitap['id'] ?>">
                                <input type="hidden" name="kitap_adi" value="<?= htmlspecialchars($kitap['baslik']) ?>">
                                <input type="hidden" name="adet" value="1">
                                <button type="submit" class="btn btn-bee">Sepete Ekle</button>
                            </form>
                        <?php else : ?>
                            <p class="mt-2"><a href="giris.php" class="text-decoration-none" style="color:#b8860b; font-weight:600;">Giriş yap</a>arak sepete kitap ekleyebilirsiniz.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="accordion mt-3" id="yorumAccordion<?= $kitap['id'] ?>">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $kitap['id'] ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $kitap['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $kitap['id'] ?>">
                                🐝 Yorumları Göster/Gizle
                            </button>
                        </h2>
                        <div id="collapse<?= $kitap['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $kitap['id'] ?>" data-bs-parent="#yorumAccordion<?= $kitap['id'] ?>">
                            <div class="accordion-body">
                                <?php
                                $kitap_id = $kitap['id'];
                                $yorumlar_sorgu = $baglanti->prepare("SELECT y.yorum, y.puan, k.ad_soyad FROM yorumlar y JOIN kullanicilar k ON y.kullanici_id = k.id WHERE y.kitap_id = ? ORDER BY y.id DESC");
                                $yorumlar_sorgu->bind_param("i", $kitap_id);
                                $yorumlar_sorgu->execute();
                                $yorumlar_result = $yorumlar_sorgu->get_result();

                                if ($yorumlar_result->num_rows > 0) {
                                    while ($yorum = $yorumlar_result->fetch_assoc()) {
                                        echo "<p><strong>" . htmlspecialchars($yorum['ad_soyad']) . "</strong> (" . htmlspecialchars($yorum['puan']) . "/5):<br>" . htmlspecialchars($yorum['yorum']) . "</p><hr>";
                                    }
                                } else {
                                    echo "<p>Henüz yorum yapılmamış.</p>";
                                }
                                $yorumlar_sorgu->close();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['kullanici_id'])) : ?>
                    <button class="btn btn-bee mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#yorumForm<?= $kitap['id'] ?>" aria-expanded="false" aria-controls="yorumForm<?= $kitap['id'] ?>">
                        Yorum ve Puan Ekle
                    </button>

                    <div class="collapse mt-3" id="yorumForm<?= $kitap['id'] ?>">
                        <form action="yorum_ve_puan_ekle.php" method="POST">
                            <input type="hidden" name="kitap_id" value="<?= htmlspecialchars($kitap['id']) ?>">
                            <div class="mb-3">
                                <label for="yorum" class="form-label">Yorum Yap:</label>
                                <textarea name="yorum" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="puan" class="form-label">Puan Ver (1-5):</label>
                                <input type="number" name="puan" class="form-control" min="1" max="5" required>
                            </div>
                            <button type="submit" class="btn btn-warning">Gönder</button>
                        </form>
                    </div>
                <?php else : ?>
                    <p class="mt-3"><a href="giris.php" style="color:#b8860b; font-weight:600;">Giriş yap</a>arak yorum ve puan ekleyebilirsiniz.</p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Bu kategoride henüz kitap bulunmamaktadır.</p>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Favori kalp ikonuna tıklanınca AJAX ile favorilere ekle/sil işlemi yapalım
document.querySelectorAll('.fav-heart').forEach(heart => {
    heart.addEventListener('click', () => {
        const kitapId = heart.getAttribute('data-kitap-id');

        fetch('favorilere_ekle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'kitap_id=' + encodeURIComponent(kitapId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                heart.classList.toggle('favorited', data.favorited);
            } else {
                alert(data.message || 'Bir hata oluştu!');
            }
        })
        .catch(() => alert('İstek gönderilemedi!'));
    });
});
</script>

</body>
</html>