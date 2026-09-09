<?php
/**
 * Imports the original static website content once, without overwriting edits
 * made later through the admin panel. Called by setup_database.php.
 */
function idtcEnsureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $check->execute([$table, $column]);
    if (!(int)$check->fetchColumn()) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

function idtcMigrateExistingContent(PDO $pdo, string $root): array {
    // Admin profile photo (Admin > Profil).
    idtcEnsureColumn($pdo, 'admin_users', 'photo', 'VARCHAR(255) NULL');
    idtcEnsureColumn($pdo, 'articles', 'display_order', 'INT NOT NULL DEFAULT 0 AFTER image_url');
    // Preserve the current public order while giving legacy articles a real
    // position number. This runs only for installations that have no order yet.
    if (!(int)$pdo->query('SELECT COUNT(*) FROM articles WHERE display_order > 0')->fetchColumn() && (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn()) {
        $legacy = $pdo->query("SELECT id FROM articles ORDER BY published_at DESC, created_at DESC, id DESC")->fetchAll(PDO::FETCH_COLUMN);
        $setOrder = $pdo->prepare('UPDATE articles SET display_order = ? WHERE id = ?');
        foreach ($legacy as $index => $id) $setOrder->execute([$index + 1, $id]);
    }
    // Older installs may have the first, smaller services schema.
    foreach (['category'=>'VARCHAR(120) NULL AFTER title', 'subtitle'=>'VARCHAR(255) NULL AFTER category', 'full_case_study'=>'LONGTEXT NULL AFTER image_url', 'metrics'=>'LONGTEXT NULL AFTER full_case_study'] as $column => $definition) idtcEnsureColumn($pdo, 'services', $column, $definition);
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_content (id INT AUTO_INCREMENT PRIMARY KEY, content_key VARCHAR(120) NOT NULL UNIQUE, section_name VARCHAR(80) NOT NULL, content_value TEXT NOT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, updated_by VARCHAR(100) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $result = ['training'=>0,'services'=>0,'gallery'=>0,'testimonials'=>0,'cms'=>0,'credentials'=>0,'findus'=>0];

    $training = [
        ['Basic Flight & Safety Training','Keterampilan Terbang Dasar & SOP Keselamatan','Pelatihan tingkat dasar yang berfokus pada penguasaan kontrol penerbangan manual, pemahaman regulasi penerbangan, serta standar keselamatan penerbangan (SOP).','Modul Disesuaikan','Pemula, Hobi, & Staff Operasional Instansi',"Keterampilan terbang presisi dari tingkat dasar hingga mahir\nFokus pada teknik penerbangan yang aman\nPemahaman prosedur operasional sesuai standar penerbangan\nPraktik langsung di lapangan dengan panduan instruktur berpengalaman"],
        ['Pengoperasian Kamera & Cinematic Footage','Dokumentasi Udara & Visual Stabil','Modul khusus yang dirancang untuk membantu peserta menghasilkan footage sinematik yang stabil, menguasai gerakan kamera udara, serta profil warna profesional.','Modul Disesuaikan','Fotografer, Videografer, & Dokumentasi Profesional',"Teknik pengoperasian kamera udara resolusi tinggi\nMembantu peserta menghasilkan footage yang stabil dan halus\nPenguasaan sudut pengambilan gambar & pergerakan smooth orbit\nPraktik pengambilan gambar di berbagai kondisi pencahayaan"],
        ['Pelatihan Drone Mapping & Fotogrametri','Pemetaan Spasial 2D/3D & Data Kontur','Pelatihan pemetaan spasial menggunakan drone untuk pembuatan peta ortomosaik 2D, kontur ketinggian, dan model 3D yang akurat untuk lahan & konstruksi.','Modul Disesuaikan','Surveyor, Komunitas Mahasiswa, & Profesional GIS',"Pemasangan dan pengukuran titik kontrol lapangan (GCP)\nPerencanaan misi terbang otonom berbasis grid survey\nPengolahan data hasil foto udara menjadi peta Ortomosaik 2D & 3D Mesh\nExport data ke software GIS dan CAD"],
        ['Pelatihan Drone Pertanian Presisi','Smart Farming & Crop Monitoring','Penguasaan drone multispektral untuk analisis kesehatan tanaman (NDVI) serta teknik penyemprotan otomatis di area lahan pertanian.','Modul Disesuaikan','Praktisi Pertanian, Perkebunan, & Peneliti',"Pengoperasian kamera multispektral pemantau vegetasi\nPemetaan indeks kesehatan tanaman (NDVI Analysis)\nKalibrasi dan penerbangan penyemprotan pupuk/pestisida otomatis\nEfisiensi penggunaan bahan dan penghematan waktu kerja"],
        ['Pelatihan Khusus Instansi & Tanggap Bencana','In-House Training & Custom Programs','Pelatihan khusus yang dirancang sesuai kebutuhan organisasi (seperti Disnaker Sleman dan Tim Tanggap Bencana) dengan jadwal dan materi yang disesuaikan.','Jadwal & Durasi Custom','Instansi Pemerintah, BUMN, & Tim Relawan Bencana',"Materi dan durasi pelatihan menyesuaikan kebutuhan organisasi\nPenerbangan pemetaan cepat area terdampak bencana (Rapid SAR Mapping)\nSimulasi pengawasan wilayah dan dokumentasi instansi\nSertifikat keahlian resmi dari Indonesia Drone Training Center"]
    ];
    if (!(int)$pdo->query('SELECT COUNT(*) FROM training_programs')->fetchColumn()) { $s=$pdo->prepare('INSERT INTO training_programs (title,subtitle,description,duration,target_audience,features,status,display_order) VALUES (?,?,?,?,?,?,\'active\',?)'); foreach ($training as $i=>$row) { $s->execute([...$row,$i+1]); $result['training']++; } }

    $projects = [
        ['GOVERNMENT & AGENCY','DISNAKER SLEMAN TRAINING','Pelatihan Drone Dinas Tenaga Kerja Kab. Sleman','Pelatihan operasional drone dan keselamatan penerbangan bagi aparatur Dinas Tenaga Kerja Kabupaten Sleman untuk efisiensi pengawasan wilayah.','https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1400&auto=format&fit=crop'],
        ['DISASTER RELIEF','TANGGAP BENCANA','Pelatihan Drone Penanggulangan Bencana','Pelatihan navigasi dan pemetaan udara fast-response bagi tim relawan serta personil penanggulangan bencana alam.','https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1400&auto=format&fit=crop'],
        ['GEOSPATIAL','DRONE MAPPING','Pelatihan Fotogrametri & Pemetaan Spasial','Pelatihan pembuatan peta ortomosaik 2D/3D, kontur ketinggian, dan pengolahan data GIS untuk tata ruang serta konstruksi.','https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=1400&auto=format&fit=crop'],
        ['SMART AGRICULTURE','DRONE PERTANIAN','Pelatihan Precision Agriculture','Pelatihan penerbangan drone multispektral untuk pemantauan vegetasi lahan dan penyemprotan pupuk presisi berbasis data.','https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=1400&auto=format&fit=crop'],
        ['ENTERPRISE FLEET','MULTI-DRONE TRAINING','Pelatihan Berbagai Jenis Wahana Drone','Pelatihan praktis pengoperasian berbagai tipe drone FPV, quadcopter, hingga fixed-wing industri dengan standar keselamatan tinggi.','https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1400&auto=format&fit=crop']
    ];
    if (!(int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn()) { $s=$pdo->prepare('INSERT INTO services (category,title,subtitle,description,image_url,status,display_order) VALUES (?,?,?,?,?,\'active\',?)'); foreach ($projects as $i=>$row) { $s->execute([...$row,$i+1]); $result['services']++; } }

    $testimonials = [
        ['Peserta Pelatihan Disnaker Kab. Sleman','Instansi Pemerintah Daerah','Pelatihan drone bersama IDTC memberikan pemahaman mendalam tentang keselamatan penerbangan dan teknik kamera yang sangat berguna untuk tugas lapangan.'],
        ['Komunitas Mahasiswa & Surveyor','Geospatial Mapping Team','Materi drone mapping dan fotogrametri disampaikan secara terstruktur. Praktik langsung membuat kami paham pemetaan topografi.'],
        ['Tim Relawan Penanggulangan Bencana','Disaster Relief Operator','Instruktur sangat berpengalaman dan komunikatif. Materi tanggap bencana dan respon cepat sangat relevan untuk simulasi di lapangan.']
    ];
    if (!(int)$pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn()) { $s=$pdo->prepare('INSERT INTO testimonials (customer_name,role,testimonial,status,display_order) VALUES (?,?,?,\'active\',?)'); foreach($testimonials as $i=>$row) { $s->execute([...$row,$i+1]); $result['testimonials']++; } }

    // Read every pre-existing gallery image from its original PHP array; category
    // headings and captions are retained verbatim, and no duplicates are added.
    if (!(int)$pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn()) {
        $source = file_get_contents($root . '/gallery.php');
        preg_match_all("/'([^']+)'\s*=>\s*\[\s*'title'\s*=>\s*'[^']*'.*?'photos'\s*=>\s*\[(.*?)\]\s*\]/s", $source, $groups, PREG_SET_ORDER);
        $insert = $pdo->prepare("INSERT INTO gallery_items (category,title,caption,image_url,display_size,status,display_order) VALUES (?,?,?,?,?,'active',?)");
        foreach ($groups as $group) { preg_match_all("/\['src'\s*=>\s*'([^']+)',\s*'caption'\s*=>\s*'([^']+)',\s*'size'\s*=>\s*'([^']+)'\]/", $group[2], $photos, PREG_SET_ORDER); foreach ($photos as $i=>$photo) { $insert->execute([$group[1],$photo[2],$photo[2],$photo[1],$photo[3],$i+1]); $result['gallery']++; } }
    }

    if (!(int)$pdo->query('SELECT COUNT(*) FROM site_content')->fetchColumn()) {
        $source = file_get_contents($root . '/index.php');
        preg_match_all('/data-i18n="([a-zA-Z0-9-]+)"[^>]*>(.*?)<\/[^>]+>/s', $source, $matches, PREG_SET_ORDER);
        $insert = $pdo->prepare('INSERT IGNORE INTO site_content (content_key,section_name,content_value,updated_by) VALUES (?,?,?,?)');
        foreach ($matches as $match) { $value=trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8')); if ($value !== '') { $insert->execute([$match[1],'Homepage',preg_replace('/\s+/', ' ', $value),'migration']); $result['cms'] += $insert->rowCount(); } }
    }
    // Network & Credentials ("TRUSTED & RECOGNIZED") content module. Seeded from the
    // original static homepage cards so the switch to dynamic rendering is invisible.
    $pdo->exec("CREATE TABLE IF NOT EXISTS credentials (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, org_name VARCHAR(180) NOT NULL,
        relationship VARCHAR(255) NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_credentials_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $credItems = [
        ['REGULATORY & AVIATION','DKPPU / Kemenhub RI','Regulasi & Keselamatan Penerbangan','https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?q=80&w=300&auto=format&fit=crop'],
        ['REGULATORY & AVIATION','AirNav Indonesia','Izin Wilayah Udara & NOTAM','https://images.unsplash.com/photo-1519074069444-1ba4edd16be1?q=80&w=300&auto=format&fit=crop'],
        ['REGULATORY & AVIATION','Disnaker Kab. Sleman','Instansi Mitra In-House Training','https://images.unsplash.com/photo-1577495508048-b635879837f1?q=80&w=300&auto=format&fit=crop'],
        ['CERTIFICATION & PROFESSIONAL STANDARDS','Sertifikasi BNSP / Pilot','Standar Kompetensi Pilot Drone','https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=300&auto=format&fit=crop'],
        ['CERTIFICATION & PROFESSIONAL STANDARDS','FASI (Aero Sport Indonesia)','Standar Navigasi & Olahraga Udara','https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=300&auto=format&fit=crop'],
        ['CERTIFICATION & PROFESSIONAL STANDARDS','ISO Safety Flight SOP','Standard Operasional & Prosedur Risk Management','https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=300&auto=format&fit=crop'],
        ['INDUSTRY & TECHNOLOGY PARTNERS','DJI Enterprise Platform','Armada Wahana Udara & Sensor','https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=300&auto=format&fit=crop'],
        ['INDUSTRY & TECHNOLOGY PARTNERS','Pix4D & Agisoft Photogrammetry','Software Pengolahan Data Spasial','https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=300&auto=format&fit=crop'],
        ['INDUSTRY & TECHNOLOGY PARTNERS','PT Jogja Creative Production','Perusahaan Induk (Parent Enterprise)','https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=300&auto=format&fit=crop']
    ];
    if (!(int)$pdo->query('SELECT COUNT(*) FROM credentials')->fetchColumn()) { $s=$pdo->prepare("INSERT INTO credentials (category,org_name,relationship,image_url,status,display_order) VALUES (?,?,?,?,'active',?)"); foreach ($credItems as $i=>$row) { $s->execute([...$row,$i+1]); $result['credentials']++; } }

    // "Find Us" contact defaults. INSERT IGNORE keeps any value already edited in admin.
    $findUs = [
        'contact-email'=>'jogjacreativeproduction@gmail.com',
        'contact-whatsapp'=>'+62 856-0060-4388',
        'contact-address'=>'Perum Jl. Griya Mlati Indah No. B4, Mulungan Kulon, Sendangadi, Kec. Mlati, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55285',
        'contact-instagram'=>'@idtc.drone'
    ];
    $findUsStmt = $pdo->prepare('INSERT IGNORE INTO site_content (content_key,section_name,content_value,updated_by) VALUES (?,?,?,?)');
    foreach ($findUs as $key => $value) { $findUsStmt->execute([$key,'Find Us',$value,'migration']); $result['findus'] += $findUsStmt->rowCount(); }

    return $result;
}
?>
