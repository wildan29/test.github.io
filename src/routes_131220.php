<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();
    $container['upload_directory'] = __DIR__ . '/uploads';

    $app->post('/register', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $user_id = $this->randString;
        $sapaan = $body->sapaan;
        $namaLengkap = $body->namaLengkap;
        $email = $body->email;
        $password = $body->password;
        $noTelp = $body->noTelp;

        try {
            $sql = "SELECT * FROM user WHERE email = :email";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$result) {
                // email belum ada
                $sql = "INSERT INTO user (user_id, sapaan, nama_lengkap, email, password, no_telp) VALUES (:user_id, :sapaan, :nama_lengkap, :email, :password, :no_telp) ";

                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':sapaan', $sapaan);
                $stmt->bindParam(':nama_lengkap', $namaLengkap);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', sha1($password));
                $stmt->bindParam(':no_telp', $noTelp);
    
                if ($stmt->execute()) {
                    return $response->withJson(array("status" => "success", "message" => "Pendaftaran berhasil!"), 200);
                } else {
                    return $response->withJson(array("status" => "failed", "message" => "Pendaftaran gagal!"), 200);
                }
            } else {
                // email sudah ada
                return $response->withJson(array("status" => "failed", "message" => "Email sudah digunakan!"), 200);
            }

            $stmt = null;
        } catch (PDOException $e) {
            return $response->withJson(array("status" => "PDOException", "message" => $e->getMessage()), 200);
        }
    });

    $app->get('/login', function ($request, $response, $args) {
        $email = $request->getQueryParams()['email'];
        $password = $request->getQueryParams()['password'];
        $password = sha1($password);
        try {
            $sql = "SELECT * FROM user WHERE email = :email AND password = :password";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $userData = array(
                    "userId" => $result['user_id'],
                    "sapaan" => $result['sapaan'],
                    "namaLengkap" => $result['nama_lengkap'],
                    "email" => $result['email'],
                    "password" => $result['password'],
                    "noTelp" => $result['no_telp']
                );

                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Login berhasil!",
                    "data" => $userData
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Email atau password salah!"
                ), 200);
            }

            $stmt = null;
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/home/banner', function ($request, $response, $args) {
        require_once("../util/simple_html_dom.php");

        $html = new simple_html_dom();
        $html->load_file('https://zakatsukses.org/');

        $arr_img_url = array();

        foreach ($html->find('.swiper-slide-image') as $elements) {
            array_push($arr_img_url, ["banner_url" => $elements->src]);
        }
        $html = null;
        return $response->withJson($arr_img_url, 200);
    });

    $app->get('/home/berita-terkini', function ($request, $response, $args) {
        require_once("../util/simple_html_dom.php");

        $html = new simple_html_dom();
        $html->load_file('https://zakatsukses.org/');

        $berita_arr = array();

        foreach ($html->find('.pt-cv-ifield') as $elements) {
            $url = $elements->children(0)->href;
            $title = $elements->children(1)->plaintext;
            $content = preg_replace('/&#8230;/', '', $elements->children(2)->plaintext);
            $img_url = $elements->children(0)->children(0)->src;
            //$img_src_set = $elements->children(0)->children(0)->srcset;

            array_push($berita_arr, array(
                "imgUrl" => $img_url,
                "title" => $title,
                "content" => $content,
                "beritaUrl" => $url
            ));
        }

        $html = null;

        return $response->withJson($berita_arr, 200);
    });

    $app->get('/logam-price', function ($request, $response, $args) {
        $url = "https://www.indogold.id/harga-emas-hari-ini";

        require_once("../util/simple_html_dom.php");

        $html = new simple_html_dom();
        $html->load_file($url);

        $element = $html->find('.Rectangle-price')[0];

        $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
        $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;

        $harga_emas = preg_replace('/\D/', '', $harga_emas);
        $harga_perak = preg_replace('/\D/', '', $harga_perak);

        $update = $html->find(".container")[4]->children(2)->plaintext;

        return $response->withJson(array(
            "sumber" => "https://www.indogold.id/",
            "harga_emas" => $harga_emas,
            "harga_perak" => $harga_perak,
            "update_terakhir" => $update
        ), 200);
    });

    function getLargestImage($srcsetString)
    {
        $images = array();
        // split on comma
        $srcsetArray = explode(",", $srcsetString);

        foreach ($srcsetArray as $srcString) {
            // split on whitespace - optional descriptor
            $imgArray = explode(" ", trim($srcString));
            // cast w or x descriptor as an Integer
            $images[(int)$imgArray[1]] = $imgArray[0];
        }
        // find the max
        $maxIndex = max(array_keys($images));
        return $images[$maxIndex];

    }

    $app->post('/pencatatan/harta', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $userId = $body->user_id;
        $kategori = $body->kategori;
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktuKepemilikan = $body->waktu_kepemilikan;
        $waktu = $body->waktu;
        $tanggal = $body->tanggal;

        try {
            $sql = "INSERT INTO emas_perak (user_id, kategori, nama_item, keterangan, kuantitas, waktu_kepemilikan, waktu, tanggal) VALUES (:user_id, :kategori, :nama_item, :keterangan, :kuantitas, :waktu_kepemilikan, :waktu, :tanggal)";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':nama_item', $namaItem);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':kuantitas', $kuantitas);
            $stmt->bindParam(':waktu_kepemilikan', $waktuKepemilikan);
            $stmt->bindParam(':waktu', $waktu);
            $stmt->bindParam(':tanggal', $tanggal);

            if ($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Penambahan item berhasil!"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Penambahan item gagal!"
                ), 200);
            }

            $stmt = null;
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/pencatatan/harta', function ($request, $response, $args) {
        $userId = $request->getQueryParams()['user_id'];
        $kategori = $request->getQueryParams()['kategori'];

        try {
            $sql = "SELECT * FROM emas_perak WHERE kategori = :kategori AND user_id = :user_id ORDER BY id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':user_id', $userId);

            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withJson($result, 200);

            $stmt = null;
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/pencatatan/harta/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "SELECT * FROM emas_perak WHERE id = :itemId AND user_id = :user_id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':itemId', $itemId);
            $stmt->bindParam(':user_id', $userId);

            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    return $response->withJson($result, 200);
                }
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->put('/pencatatan/harta/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        $body = json_decode($request->getBody());
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktu_kepemilikan = $body->waktu_kepemilikan;

        try {
            $sql = "UPDATE emas_perak SET nama_item = :nama_item, keterangan = :keterangan, kuantitas = :kuantitas, waktu_kepemilikan = :waktu_kepemilikan WHERE id = :item_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nama_item', $namaItem);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':kuantitas', $kuantitas);
            $stmt->bindParam(':waktu_kepemilikan', $waktu_kepemilikan);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':user_id', $userId);
            if ($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan item berhasil!"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Perubahan item gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->delete('/pencatatan/harta/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "DELETE FROM emas_perak WHERE id = :item_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':user_id', $userId);
            if ($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan item berhasil!"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Perubahan item gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->post('/pencatatan/keuangan', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $userId = $body->user_id;
        $nominal = $body->nominal;
        $jenisPencatatan = $body->jenis_pencatatan;
        $keterangan = $body->keterangan;
        $waktu = $body->waktu;
        $tanggal = $body->tanggal;
        $bulan = $body->bulan;
        $tahun = $body->tahun;

        try {
            $sql = "INSERT INTO keuangan (user_id, nominal, jenis_pencatatan, keterangan, waktu, tanggal, bulan, tahun) VALUES (:user_id, :nominal, :jenis_pencatatan, :keterangan, :waktu, :tanggal, :bulan, :tahun)";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':nominal', $nominal);
            $stmt->bindParam(':jenis_pencatatan', $jenisPencatatan);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':waktu', $waktu);
            $stmt->bindParam(':tanggal', $tanggal);
            $stmt->bindParam(':bulan', $bulan);
            $stmt->bindParam(':tahun', $tahun);

            if ($stmt->execute()) {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Penambahan item berhasil!"
                ), 200);
            } else {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Penambahan item gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->put('/pencatatan/keuangan', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $id = $body->id;
        $userId = $body->user_id;
        $nominal = $body->nominal;
        $keterangan = $body->keterangan;

        try {
            $sql = "UPDATE keuangan SET nominal = :nominal, keterangan = :keterangan WHERE id = :id AND user_id = :user_id";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':nominal', $nominal);
            $stmt->bindParam(':keterangan', $keterangan);

            if ($stmt->execute()) {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan item berhasil!"
                ), 200);
            } else {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Perubahan item gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/pencatatan/keuangan', function ($request, $response, $args) {
        define("PEMASUKAN", "Pemasukan");
        define("PENGELUARAN", "Pengeluaran");

        $userId = $request->getQueryParams()['user_id'];
        $tanggal = $request->getQueryParams()['tanggal'];
        $bulan = $request->getQueryParams()['bulan'];
        $tahun = $request->getQueryParams()['tahun'];

        try {
            $sql = "SELECT * FROM keuangan WHERE user_id = :user_id AND tanggal = :tanggal AND bulan = :bulan AND tahun = :tahun";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':tanggal', $tanggal, PDO::PARAM_INT);
            $stmt->bindParam(':bulan', $bulan, PDO::PARAM_INT);
            $stmt->bindParam(':tahun', $tahun, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // menyiapkan variable penting.
            $posisi_saldo = 0;
            $saldo_bulan_lalu = 0;
            $pemasukan_bulan_ini = 0;
            $pengeluaran_bulan_ini = 0;
            $saldo_bulan_ini = 0;
            $arr_data_harian = array();

            // Query item per hari
            if (count($result) > 0) {
                foreach ($result as $value) {
                    if ($value['jenis_pencatatan'] == PEMASUKAN) {
                        $posisi_saldo = $posisi_saldo + $value['nominal'];
                    } else if ($value['jenis_pencatatan'] == PENGELUARAN) {
                        $posisi_saldo = $posisi_saldo - $value['nominal'];
                    }

                    array_push($arr_data_harian, array(
                        "id" => $value['id'],
                        "user_id" => $value['user_id'],
                        "nominal" => $value['nominal'],
                        "jenis_pencatatan" => $value['jenis_pencatatan'],
                        "keterangan" => $value['keterangan'],
                        "waktu" => $value['waktu'],
                        "tanggal" => $value['tanggal'],
                        "bulan" => $value['bulan'],
                        "tahun" => $value['tahun'],
                        "posisi_saldo" => $posisi_saldo
                    ));
                }
            }

            // Query bulan lalu
            $bulan_lalu = ($bulan == 1) ? 12 : $bulan - 1;
            foreach ($this->db->query("SELECT nominal, jenis_pencatatan FROM keuangan WHERE bulan = $bulan_lalu")->fetchAll(PDO::FETCH_ASSOC) as $value) {
                if ($value['jenis_pencatatan'] == PEMASUKAN) {
                    $saldo_bulan_lalu = $saldo_bulan_lalu + $value['nominal'];
                } else if ($value['jenis_pencatatan'] == PENGELUARAN) {
                    $saldo_bulan_lalu = $saldo_bulan_lalu - $value['nominal'];
                }
            }

            // Query Bulan ini
            foreach ($this->db->query("SELECT nominal, jenis_pencatatan FROM keuangan WHERE bulan = $bulan")->fetchAll(PDO::FETCH_ASSOC) as $value) {
                if ($value['jenis_pencatatan'] == PEMASUKAN) {
                    $pemasukan_bulan_ini = $pemasukan_bulan_ini + $value['nominal'];
                }

                if ($value['jenis_pencatatan'] == PENGELUARAN) {
                    $pengeluaran_bulan_ini = $pengeluaran_bulan_ini + $value['nominal'];
                }

                if ($value['jenis_pencatatan'] == PEMASUKAN) {
                    $saldo_bulan_ini = $saldo_bulan_ini + $value['nominal'];
                } else if ($value['jenis_pencatatan'] == PENGELUARAN) {
                    $saldo_bulan_ini = $saldo_bulan_ini - $value['nominal'];
                }
            }

            $arr_result = array(
                "saldo_bulan_lalu" => $saldo_bulan_lalu,
                "pemasukan_bulan_ini" => $pemasukan_bulan_ini,
                "pengeluaran_bulan_ini" => $pengeluaran_bulan_ini,
                "saldo_bulan_ini" => $saldo_bulan_ini,
                "data_harian" => array_reverse($arr_data_harian)
            );

            $stmt = null;
            return $response->withJson($arr_result, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/pencatatan/keuangan/{id}', function ($request, $response, $args) {

    });

    $app->delete('/pencatatan/keuangan/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "DELETE FROM keuangan WHERE id = :item_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':item_id', $itemId);
            $stmt->bindParam(':user_id', $userId);
            if ($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan item berhasil!"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Perubahan item gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    function moveUploadedFile($directory, Slim\Http\UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    $app->post('/pembayaran/zakat', function ($request, $response, $args) {
        $directory = $this->get('upload_directory');

        $uploadedFiles = $request->getUploadedFiles();

        $photo_uploaded = false;
        $filename = null;

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['photo'];

        $idTransaksi = $request->getParam('idTransaksi');
        $jenisZakat = $request->getParam('jenis_zakat');
        $keteranganZakat = $request->getParam('keterangan_zakat');
        $nominal = $request->getParam('nominal');
        $userId = $request->getParam('user_id');
        $tanggal = $request->getParam('tanggal_dibuat');
        $bankTujuan = $request->getParam('bank_di_tuju');
        $noRekBank = $request->getParam('no_rek_bank');

        // Upload Photo
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $photo_uploaded = true;
        }

        if ($photo_uploaded) {
            try {
                $sql = "INSERT INTO zakat (id_transaksi, jenis_zakat, nominal, bank_tujuan, no_rek_bank, ket_zakat, user_id, bukti_pembayaran, tanggal_dibuat) 
                VALUES (:id_transaksi, :jenis_zakat, :nominal, :bank_tujuan, :no_rek_bank, :ket_zakat, :user_id, :bukti_pembayaran, :tanggal_dibuat)";

                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id_transaksi', $idTransaksi);
                $stmt->bindParam(':jenis_zakat', $jenisZakat);
                $stmt->bindParam(':nominal', $nominal);
                $stmt->bindParam(':ket_zakat', $keteranganZakat);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':bukti_pembayaran', $filename);
                $stmt->bindParam(':tanggal_dibuat', $tanggal);
                $stmt->bindParam(':bank_tujuan', $bankTujuan);
                $stmt->bindParam(':no_rek_bank', $noRekBank);

                if ($stmt->execute()) {
                    $message = "Pembayaran Zakat " . $idTransaksi . " sedang di proses, silahkan menunggu";

                    $stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                    $stmt_notif->bindParam(':user_id', $userId);
                    $stmt_notif->bindParam(':notifikasi', $message);

                    if($stmt_notif->execute()) {
                        require '../util/MyNotification.php';

                        $notif = new MyNotification();
                        $notif->initDb($this->db);
                        $notif->getTokenFromUserId($userId);
                        $notif->send($message);
                    }

                    return $response->withJson(array(
                        "status" => "success",
                        "message" => "pembayaran berhasil!"
                    ), 200);
                } else {
                    return $response->withJson(array(
                        "status" => "failed",
                        "message" => "pembayaran error!"
                    ), 200);
                }
            } catch (PDOException $e) {
                return $response->withJson(array(
                    "status" => "PDOException",
                    "message" => $e->getMessage()
                ), 200);
            }
        }
    });

    $app->post('/pembayaran/infak', function ($request, $response, $args) {
        $directory = $this->get('upload_directory');

        $uploadedFiles = $request->getUploadedFiles();

        $photo_uploaded = false;
        $filename = null;

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['photo'];

        $idTransaksi = $request->getParam('idTransaksi');
        $penyaluran = $request->getParam('penyaluran');
        $keterangan = $request->getParam('keterangan');
        $nominal = $request->getParam('nominal');
        $userId = $request->getParam('user_id');
        $tanggal = $request->getParam('tanggal_dibuat');
        $bankTujuan = $request->getParam('bank_di_tuju');
        $noRekBank = $request->getParam('no_rek_bank');

        // Upload Photo
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($directory, $uploadedFile);
            $photo_uploaded = true;
        }

        if ($photo_uploaded) {
            try {
                $sql = "INSERT INTO infak (id_transaksi, penyaluran, nominal_donasi, keterangan, bank_tujuan, no_rek_bank, user_id, bukti_pembayaran, tanggal_dibuat) 
                VALUES (:id_transaksi, :penyaluran, :nominal_donasi, :keterangan, :bank_tujuan, :no_rek_bank, :user_id, :bukti_pembayaran, :tanggal_dibuat)";

                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id_transaksi', $idTransaksi);
                $stmt->bindParam(':penyaluran', $penyaluran);
                $stmt->bindParam(':nominal_donasi', $nominal);
                $stmt->bindParam(':keterangan', $keterangan);
                $stmt->bindParam(':bank_tujuan', $bankTujuan);
                $stmt->bindParam(':no_rek_bank', $noRekBank);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':bukti_pembayaran', $filename);
                $stmt->bindParam(':tanggal_dibuat', $tanggal);

                if ($stmt->execute()) {
                    $message = "Pembayaran Infak " . $idTransaksi . " sedang di proses, silahkan menunggu";

                    $stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                    $stmt_notif->bindParam(':user_id', $userId);
                    $stmt_notif->bindParam(':notifikasi', $message);

                    if($stmt_notif->execute()) {
                        require '../util/MyNotification.php';

                        $notif = new MyNotification();
                        $notif->initDb($this->db);
                        $notif->getTokenFromUserId($userId);
                        $notif->send($message);
                    }

                    return $response->withJson(array(
                        "status" => "success",
                        "message" => "pembayaran berhasil!"
                    ), 200);
                } else {
                    return $response->withJson(array(
                        "status" => "failed",
                        "message" => "pembayaran error!"
                    ), 200);
                }
            } catch (PDOException $e) {
                return $response->withJson(array(
                    "status" => "PDOException",
                    "message" => $e->getMessage()
                ), 200);
            }
        }
    });

    $app->get('/set-token', function ($request, $response, $args) {
        $userId = $request->getQueryParams()['userId'];
        $idToken = $request->getQueryParams()['idToken'];

        try {
            $sql = "INSERT INTO fcm_tokens (user_id, token) VALUES (:uid, :token) ON DUPLICATE KEY UPDATE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':uid', $userId);
            $stmt->bindParam(':token', $idToken);
            $stmt->execute();
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/get-notification', function ($request, $response, $args) {
        $userId = $request->getQueryParams()['userId'];

        try {
            $sql = "SELECT * FROM notifikasi WHERE user_id = :user_id ORDER BY id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $response->withJson($result, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/update-read-notification', function($request, $response) {
        $userId = $request->getQueryParams()['userId'];

        try {
            $sql = "UPDATE notifikasi SET dibaca = '1' WHERE user_id = :user_id AND dibaca = '0'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/bukti-transaksi", function($request, $response) {
        $userId = $request->getQueryParams()['userId'];

        $sql_zakat = "SELECT zakat.id_transaksi, zakat.jenis_zakat, zakat.ket_zakat, zakat.nominal, zakat.tanggal_dibuat FROM zakat, user
        WHERE zakat.user_id = user.user_id AND zakat.dikonfirmasi = '1' AND user.user_id = :user_id";

        $sql_infak = "SELECT infak.id_transaksi, infak.penyaluran, infak.keterangan, infak.nominal_donasi, infak.tanggal_dibuat FROM infak, user 
        WHERE infak.user_id = user.user_id AND infak.dikonfirmasi = '1' AND user.user_id = :user_id";
    
        try {
            $stmt_zakat = $this->db->prepare($sql_zakat);
            $stmt_zakat->bindParam(':user_id', $userId);
            $stmt_zakat->execute();
            $result_zakat = $stmt_zakat->fetchAll(PDO::FETCH_ASSOC);

            $stmt_infak = $this->db->prepare($sql_infak);
            $stmt_infak->bindParam(':user_id', $userId);
            $stmt_infak->execute();
            $result_infak = $stmt_infak->fetchAll(PDO::FETCH_ASSOC);

            $arr_merged = array_merge($result_zakat, $result_infak);

            usort($arr_merged, function($a, $b) {
                return strtotime($a['tanggal_dibuat']) - strtotime($b['tanggal_dibuat']);
            });

            $result_zakat = null;
            $result_infak = null;

            return $response->withJson(array_reverse($arr_merged), 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/bukti-transaksi/{idTransaksi}", function($request, $response, $args) {
        $idTransaksi = $args['idTransaksi'];
        $jenis = explode("-", $idTransaksi);
        $sql = null;
        
        try {
            if($jenis[0] == "01") {
                //Zakat
                $sql = "SELECT * FROM zakat WHERE id_transaksi = :id_transaksi";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id_transaksi', $idTransaksi);
                $stmt->execute();
    
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $response->withJson($result, 200);
            } else if ($jenis[0] == "02") {
                //Infak
                $sql = "SELECT * FROM infak WHERE id_transaksi = :id_transaksi";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id_transaksi', $idTransaksi);
                $stmt->execute();
    
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $response->withJson($result, 200);
            }
        } catch(PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/get-admin-whatsapp", function($request, $response) {
        try {
            $sql = "SELECT number FROM whatsapp_admin WHERE user = 'admin'";
            $result = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
            return $response->withJson($result, 200);   
        } catch(PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/user/{userId}", function($request, $response, $args) {
        $userId = $args['userId'];

        try {
            $sql = "SELECT nama_lengkap, email, no_telp FROM user WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            //unset($result['password']);

            return $response->withJson($result, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->put("/user/{userId}", function($request, $response, $args) {
        $userId = $args['userId'];
        $namaLengkap = $request->getQueryParams()['namaLengkap'];
        $email = $request->getQueryParams()['email'];
        $noTelp = $request->getQueryParams()['noTelp'];

        try {
            $sql = "UPDATE user SET nama_lengkap = :nama_lengkap, email = :email, no_telp = :no_telp WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nama_lengkap', $namaLengkap);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':no_telp', $noTelp);
            $stmt->bindParam(':user_id', $userId);
            
            if($stmt->execute()) {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan berhasil!"
                ), 200);
            } else {
                $stmt = null;
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Perubahan gagal!"
                ), 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/payment-proses", function ($request, $response, $args) {
        $userId = $request->getQueryParams()['userId'];

        try {
            $sql_zakat = "SELECT id_transaksi, tanggal_dibuat FROM zakat WHERE user_id = :user_id AND dikonfirmasi = '0'";
            $sql_infak = "SELECT id_transaksi, tanggal_dibuat FROM infak WHERE user_id = :user_id AND dikonfirmasi = '0'";

            $stmt_zakat = $this->db->prepare($sql_zakat);
            $stmt_zakat->bindParam(':user_id', $userId);
            $stmt_zakat->execute();
            $result_zakat = $stmt_zakat->fetchAll(PDO::FETCH_ASSOC);

            $stmt_infak = $this->db->prepare($sql_infak);
            $stmt_infak->bindParam(':user_id', $userId);
            $stmt_infak->execute();
            $result_infak = $stmt_infak->fetchAll(PDO::FETCH_ASSOC);

            $arr_merged = array_merge($result_zakat, $result_infak);

            usort($arr_merged, function($a, $b) {
                return strtotime($a['tanggal_dibuat']) - strtotime($b['tanggal_dibuat']);
            });

            $result_zakat = null;
            $result_infak = null;
            

            return $response->withJson(array_reverse($arr_merged), 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    /*
     * Admin Section*/

    $app->get('/admin/search-user', function ($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {

            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $query = $request->getQueryParams()['query'];

        if (empty($query)) {
            return $response->withJson(array("status" => "failed", "message" => "search query is empty!"), 200);
        }

        try {
            $sql = "SELECT * FROM user WHERE nama_lengkap LIKE :nama_lengkap";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(":nama_lengkap", '%' . $query . '%');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                $arr = array();
                foreach ($result as $user) {
                    unset($user['password']);
                    array_push($arr, $user);
                }

                return $response->withJson($arr, 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "user tidak ditemukan!"
                ), 200);
            }

        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/admin/transaksi', function($request, $response) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        try {
            $sql_zakat = null;
            $sql_infak = null;
            if($request->getQueryParams()['option'] == 0) {
                // belum di konfirmasi

                $sql_zakat = "SELECT zakat.id_transaksi, zakat.nominal, zakat.tanggal_dibuat, zakat.dikonfirmasi, user.nama_lengkap, user.email FROM zakat, user
                WHERE zakat.dikonfirmasi = '0' AND zakat.user_id = user.user_id";

                $sql_infak = "SELECT infak.id_transaksi, infak.nominal_donasi, infak.tanggal_dibuat, infak.dikonfirmasi, user.nama_lengkap, user.email FROM infak, user 
                WHERE infak.dikonfirmasi = '0' AND infak.user_id = user.user_id";
            } else if ($request->getQueryParams()['option'] == 1){
                // sudah di konfirmasi
                
                $sql_zakat = "SELECT zakat.id_transaksi, zakat.nominal, zakat.tanggal_dibuat, zakat.dikonfirmasi, user.nama_lengkap, user.email FROM zakat, user
                WHERE zakat.dikonfirmasi = '1' AND zakat.user_id = user.user_id";

                $sql_infak = "SELECT infak.id_transaksi, infak.nominal_donasi, infak.tanggal_dibuat, infak.dikonfirmasi, user.nama_lengkap, user.email FROM infak, user 
                WHERE infak.dikonfirmasi = '1' AND infak.user_id = user.user_id";
            }

            $result_zakat = $this->db->query($sql_zakat)->fetchAll(PDO::FETCH_ASSOC);
            $result_infak = $this->db->query($sql_infak)->fetchAll(PDO::FETCH_ASSOC);

            $arr_merged = array_merge($result_zakat, $result_infak);

            usort($arr_merged, function($a, $b) {
                return strtotime($a['tanggal_dibuat']) - strtotime($b['tanggal_dibuat']);
            });

            $result_zakat = null;
            $result_infak = null;

            return $response->withJson($arr_merged, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get('/admin/transaksi/{idTransaksi}', function($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $idTransaksi = $args['idTransaksi'];

        try {
            
            $jenis = explode("-", $idTransaksi);
            $sql = null;
            if($jenis[0] == "01") {
                //Zakat

                $sql = "SELECT zakat.id_transaksi, zakat.jenis_zakat, zakat.nominal, zakat.bank_tujuan, zakat.no_rek_bank, zakat.ket_zakat, zakat.user_id, 
                zakat.bukti_pembayaran, zakat.tanggal_dibuat, zakat.dikonfirmasi, zakat.on_update, user.nama_lengkap, user.email, user.no_telp FROM zakat, user
                WHERE zakat.id_transaksi = :id_transaksi";
            } else if ($jenis[0] == "02") {
                //Infak

                $sql = "SELECT infak.id_transaksi, infak.penyaluran, infak.nominal_donasi, infak.keterangan, infak.bank_tujuan, infak.no_rek_bank, infak.user_id, 
                infak.bukti_pembayaran, infak.dikonfirmasi, infak.tanggal_dibuat, infak.on_update, user.nama_lengkap, user.email, user.no_telp FROM infak, user 
                WHERE infak.id_transaksi = :id_transaksi";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_transaksi', $idTransaksi);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if($result) {
                return $response->withJson($result, 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "transaction not found!"
                ), 200);
            }

        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/admin/search-transaksi", function ($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $idTransaksi = $request->getQueryParams()['idTransaksi'];

        try {
            $jenis = explode("-", $idTransaksi);
            $sql = null;
            if($jenis[0] == "01") {
                //Zakat
                $sql = "SELECT zakat.id_transaksi, zakat.nominal, zakat.tanggal_dibuat, zakat.dikonfirmasi, user.nama_lengkap, user.email FROM zakat, user
                WHERE zakat.id_transaksi = :id_transaksi";
            } else if ($jenis[0] == "02") {
                //Infak
                $sql = "SELECT infak.id_transaksi, infak.nominal_donasi, infak.tanggal_dibuat, infak.dikonfirmasi, user.nama_lengkap, user.email FROM infak, user 
                WHERE infak.id_transaksi = :id_transaksi";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_transaksi', $idTransaksi);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if($result) {
                return $response->withJson(array($result), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "transaction not found!"
                ), 404);
            }

        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/admin/konfirmasi-pembayaran", function ($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $userId = $request->getQueryParams()['userId'];
        $status = $request->getQueryParams()['status'];
        $idTransaksi = $request->getQueryParams()['idTransaksi'];
        $date = $request->getQueryParams()['date'];

        try {
            $jenis = explode("-", $idTransaksi);
            $sql = null;
            $message = null;
            if($jenis[0] == "01") {
                //Zakat
                $sql = "UPDATE zakat SET dikonfirmasi = :status, on_update = :date WHERE id_transaksi = :id_transaksi";
                if($status == '1') {
                    $message = "Pembayaran Zakat " . $idTransaksi . " telah di konfirmasi, silahkan cek bukti transaksi untuk rincian";
                } else {
                    $message = "Pembayaran Zakat " . $idTransaksi . " telah di tolak.";
                }

            } else if ($jenis[0] == "02") {
                //Infak
                $sql = "UPDATE infak SET dikonfirmasi = :status, on_update = :date WHERE id_transaksi = :id_transaksi";
                if($status == '1') {
                    $message = "Pembayaran Infak " . $idTransaksi . " telah di konfirmasi, silahkan cek bukti transaksi untuk rincian";
                } else {
                    $message = "Pembayaran Infak " . $idTransaksi . " telah di tolak.";
                }

            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id_transaksi', $idTransaksi);
            $stmt->bindParam(':date', $date);
            if($stmt->execute()) {

                $stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                $stmt_notif->bindParam(':user_id', $userId);
                $stmt_notif->bindParam(':notifikasi', $message);

                if($stmt_notif->execute()) {
                    require '../util/MyNotification.php';

                    $notif = new MyNotification();
                    $notif->initDb($this->db);
                    $notif->getTokenFromUserId($userId);
                    $notif->send($message);
                }

                return $response->withJson(array(
                    "status" => "success",
                    "message" => "record succesfully updated"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "something wrong!"
                ), 200);
            }
        } catch(PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }

    });

    $app->get("/admin/user-transaction", function($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $userId = $request->getQueryParams()['userId'];

        $sql_zakat = "SELECT zakat.id_transaksi, zakat.jenis_zakat, zakat.ket_zakat, zakat.nominal, zakat.tanggal_dibuat, zakat.dikonfirmasi FROM zakat, user
        WHERE zakat.user_id = user.user_id AND zakat.user_id = :user_id";

        $sql_infak = "SELECT infak.id_transaksi, infak.penyaluran, infak.keterangan, infak.nominal_donasi, infak.tanggal_dibuat, infak.dikonfirmasi FROM infak, user 
        WHERE infak.user_id = user.user_id AND infak.user_id = :user_id";
    
        try {
            $stmt_zakat = $this->db->prepare($sql_zakat);
            $stmt_zakat->bindParam(':user_id', $userId);
            $stmt_zakat->execute();
            $result_zakat = $stmt_zakat->fetchAll(PDO::FETCH_ASSOC);

            $stmt_infak = $this->db->prepare($sql_infak);
            $stmt_infak->bindParam(':user_id', $userId);
            $stmt_infak->execute();
            $result_infak = $stmt_infak->fetchAll(PDO::FETCH_ASSOC);

            $arr_merged = array_merge($result_zakat, $result_infak);

            usort($arr_merged, function($a, $b) {
                return strtotime($a['tanggal_dibuat']) - strtotime($b['tanggal_dibuat']);
            });

            $stmt_zakat = null;
            $stmt_infak = null;

            return $response->withJson($arr_merged, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->put("/admin/set-admin-whatsapp", function($request, $response) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $number = $request->getQueryParams()['number'];

        try {
            $sql = "UPDATE whatsapp_admin SET number = :number WHERE user = 'admin'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':number', $number);
            
            if($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "No. Whatsapp berhasil disimpan!"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Terjadi kesalahan penyimpanan"
                ), 200);
            }

        } catch(PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->get("/admin/user/{userId}", function ($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $userId = $args['userId'];
    
        try {
            $sql = "SELECT nama_lengkap, email, no_telp FROM user WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $response->withJson($result, 200);

        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });

    $app->put("/admin/user/{userId}", function($request, $response, $args) {
        if ($request->getQueryParams()['key'] != $this->admin_key) {
            // admin key is invalid
            return $response->withJson(array("status" => "failed", "message" => "authentication failed!"), 200);
        }

        $userId = $args['userId'];
        $namaLengkap = $request->getQueryParams()['namaLengkap'];
        $email = $request->getQueryParams()['email'];
        $noTelp = $request->getQueryParams()['noTelp'];

        try {
            
            $sql = "UPDATE user SET nama_lengkap = :nama_lengkap, email = :email, no_telp = :no_telp WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':nama_lengkap', $namaLengkap);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':no_telp', $noTelp);
            $stmt->bindParam(':user_id', $userId);
            
            if($stmt->execute()) {
                return $response->withJson(array(
                    "status" => "success",
                    "message" => "Perubahan pengguna berhasil"
                ), 200);
            } else {
                return $response->withJson(array(
                    "status" => "failed",
                    "message" => "Terjadi kesalahan penyimpanan"
                ), 200);
            }

        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });
};