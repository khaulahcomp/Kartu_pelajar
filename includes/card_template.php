<?php
/**
 * card_template.php
 * Membutuhkan variabel: $student (array), $settings (array)
 * Menampilkan 1 buah desain kartu pelajar (ukuran KTP/CR-80: 85.6mm x 54mm)
 */
$logoUrl = !empty($settings['logo']) ? UPLOAD_LOGO_URL . $settings['logo'] : null;
$photoUrl = !empty($student['photo']) ? UPLOAD_PHOTO_URL . $student['photo'] : null;
$cardNumber = $student['card_number'] ?: ('ID-' . str_pad($student['id'], 5, '0', STR_PAD_LEFT));

// Warna latar kartu: bisa otomatis mengikuti warna utama, atau warna
// kustom yang dipilih admin di menu Pengaturan. Dibuat gradasi lembut
// (smooth) dengan tingkat kepekatan (opacity) yang juga bisa diatur.
$cardBaseHex = !empty($settings['theme_card_bg']) ? $settings['theme_card_bg'] : ($settings['theme_primary'] ?? '#1a3c6e');
$cardOpacityPercent = isset($settings['theme_card_opacity']) ? (int) $settings['theme_card_opacity'] : 12;
$cardOpacityPercent = max(0, min(40, $cardOpacityPercent));
$cardOpacity = $cardOpacityPercent / 100;
$cardOpacityEnd = $cardOpacity * 0.4;

[$cr, $cg, $cb] = hex_to_rgb($cardBaseHex);
[$sr, $sg, $sb] = hex_to_rgb($settings['theme_secondary'] ?? '#f4b400');
$cardBgStyle = sprintf(
    'background: linear-gradient(165deg, rgba(%d,%d,%d,%.2F) 0%%, rgba(%d,%d,%d,%.2F) 55%%, #ffffff 100%%);',
    $cr, $cg, $cb, $cardOpacity, $sr, $sg, $sb, $cardOpacityEnd
);
?>
<div class="id-card" style="<?= h($cardBgStyle) ?>">
  <div class="card-header">
    <?php if ($logoUrl): ?><img src="<?= h($logoUrl) ?>" class="logo" alt="Logo"><?php endif; ?>
    <div class="school-text">
      <div class="school-name"><?= h($settings['school_name']) ?></div>
      <?php if (!empty($settings['address'])): ?>
        <div class="school-sub"><?= h($settings['address']) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-strip"></div>
  <div class="card-title-band">Kartu Pelajar</div>
  <div class="card-body">
    <div class="photo-box">
      <?php if ($photoUrl): ?>
        <img src="<?= h($photoUrl) ?>" alt="Foto">
      <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:6px;">Tanpa Foto</div>
      <?php endif; ?>
    </div>
    <div class="info">
      <div class="name"><?= h($student['full_name']) ?></div>
      <div class="row"><span class="lbl">NISN/NIS</span><span class="val">: <?= h($student['nisn'] ?: $student['nis'] ?: '-') ?></span></div>
      <div class="row"><span class="lbl">TTL</span><span class="val">: <?= h(ttl($student['pob'], $student['dob'])) ?></span></div>
      <div class="row"><span class="lbl">Alamat</span><span class="val">: <?= h(mb_strimwidth($student['address'] ?? '-', 0, 85, '...')) ?></span></div>
    </div>
  </div>
  <div class="card-footer">
    <?= h($cardNumber) ?><?= !empty($settings['card_validity']) ? ' &middot; ' . h($settings['card_validity']) : '' ?>
  </div>
</div>
