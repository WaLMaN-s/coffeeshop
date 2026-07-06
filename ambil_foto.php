<?php
/**
 * Unduh foto menu dari Openverse (gambar berlisensi bebas) sekali jalan.
 * CLI: /opt/lampp/bin/php ambil_foto.php
 */
if (PHP_SAPI !== 'cli') exit("Jalankan dari CLI.\n");
require __DIR__ . '/config/config.php';

$kueri = [
    'Espresso'                => 'espresso shot cup',
    'Americano'               => 'americano coffee cup',
    'Cafe Latte'              => 'caffe latte art',
    'Cappuccino'              => 'cappuccino foam cup',
    'Kopi Susu Gula Aren'     => 'iced coffee milk glass',
    'Caramel Macchiato'       => 'caramel macchiato coffee',
    'V60 Manual Brew'         => 'pour over coffee v60',
    'Cold Brew'               => 'cold brew coffee glass',
    'Chocolate'               => 'hot chocolate drink mug',
    'Matcha Latte'            => 'matcha latte green',
    'Red Velvet Latte'        => 'red velvet drink latte',
    'Lemon Tea'               => 'iced lemon tea glass',
    'Lychee Tea'              => 'lychee iced tea',
    'Teh Tarik'               => 'teh tarik milk tea',
    'French Fries'            => 'french fries bowl',
    'Pisang Goreng Keju'      => 'fried banana dessert',
    'Croissant'               => 'croissant pastry',
    'Roti Bakar Cokelat Keju' => 'chocolate toast bread',
    'Donat Gula'              => 'sugar donut',
];

function http_get(string $url, int $timeout = 25): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'LorongKopi/1.0 (demo project)',
    ]);
    $isi  = curl_exec($ch);
    $kode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ($isi !== false && $kode === 200) ? $isi : null;
}

$dir  = __DIR__ . '/uploads/menu/';
$menu = $db->query('SELECT id, nama, foto FROM menu')->fetchAll();

foreach ($menu as $m) {
    if ($m['foto']) { echo "- {$m['nama']}: sudah ada foto, lewati\n"; continue; }
    $q = $kueri[$m['nama']] ?? ($m['nama'] . ' food');
    $json = http_get('https://api.openverse.org/v1/images/?q=' . rawurlencode($q)
        . '&page_size=8&license_type=commercial&aspect_ratio=wide,square');
    $hasil = $json ? (json_decode($json, true)['results'] ?? []) : [];

    $tersimpan = false;
    foreach ($hasil as $r) {
        $url = $r['url'] ?? '';
        if (!$url) continue;
        $data = http_get($url, 30);
        if (!$data || strlen($data) < 20000) continue; // terlalu kecil = kualitas buruk
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $data);
        $info = @getimagesize($tmp);
        if (!$info || $info[0] < 500 || $info[1] < 350) { unlink($tmp); continue; }

        // Simpan ulang sebagai JPEG (seragam + buang metadata)
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => @imagecreatefromwebp($tmp),
            default        => null,
        };
        unlink($tmp);
        if (!$src) continue;

        // Kecilkan bila terlalu besar (maks lebar 900px)
        $w = imagesx($src); $h = imagesy($src);
        if ($w > 900) {
            $nw = 900; $nh = (int) round($h * 900 / $w);
            $kecil = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($kecil, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $kecil;
        }
        $namaFile = 'menu_' . $m['id'] . '_' . bin2hex(random_bytes(3)) . '.jpg';
        imagejpeg($src, $dir . $namaFile, 84);
        imagedestroy($src);

        $db->prepare('UPDATE menu SET foto = ? WHERE id = ?')->execute([$namaFile, $m['id']]);
        echo "+ {$m['nama']}: $namaFile (" . $info[0] . 'x' . $info[1] . ")\n";
        $tersimpan = true;
        break;
    }
    if (!$tersimpan) echo "! {$m['nama']}: tidak dapat foto, dilewati\n";
    usleep(400000); // sopan ke API
}
echo "Selesai.\n";
