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
            if ($stmt->execute() !== false && $stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $userData = array(
                    "userId" => $result['user_id'],
                    "sapaan" => $result['sapaan'],
                    "namaLengkap" => $result['nama_lengkap'],
                    "email" => $result['email'],
                    "password" => $result['password'],
                    "noTelp" => $result['no_telp']
                );
                
                $userId = $result['user_id'];
                $tgl = date("d-m-Y");
                $ktgr = "Emas";
                $kondisi = 'ok';
                $status_emas = null;
                $sql_emas = "SELECT id, harga FROM harga_emas WHERE tanggal = :tgl";
                $stmt_emas = $this->db->prepare($sql_emas);
                $stmt_emas->bindParam(':tgl', $tgl);
                if ($stmt_emas->execute() !== false) {
                    $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
                    $sql_kenaikan_gold = "SELECT id, tanggal, perubahan, status FROM kenaikan_nilai WHERE kategori = :kategori";
                    $sql_ratarata_emas = "SELECT perubahan, status FROM harga_emas";
                    $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
                    $update_ratarata_emas = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
                    $input_ratarata_emas = "INSERT INTO kenaikan_nilai (kategori, tanggal, perubahan, status) VALUES (:kategori, :tgl, :perubahan, :status)";
                    if($stmt_emas->rowCount() == 0){
                        $stmt_emas_last = $this->db->prepare($sql_emas_last);
                        if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                            $result_emas = $stmt_emas_last->fetch(PDO::FETCH_ASSOC);
                            
                            $url = "https://www.indogold.id/harga-emas-hari-ini";
                            
                            require_once("../util/simple_html_dom.php");
                
                            $html = new simple_html_dom();
                            $html->load_file($url);
                
                            $element = $html->find('.Rectangle-price')[0];
                            $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                            $harga_emas = preg_replace('/\D/', '', $harga_emas);
                            
                            if($harga_emas > $result_emas['harga']){
                                $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                                $status = '+';
                            }else{
                                $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                                $status = '-';
                            }
                            
                            $stmt_input_emas = $this->db->prepare($input_emas);
                            $stmt_input_emas->bindParam(':tanggal', $tgl);
                            $stmt_input_emas->bindParam(':harga', $harga_emas);
                            $stmt_input_emas->bindParam(':perubahan', $perubahan);
                            $stmt_input_emas->bindParam(':status', $status);
                            $stmt_input_emas->bindParam(':sumber', $url);
                
                            if ($stmt_input_emas->execute() == false) {
                                $kondisi = "Error";
                            }
                        }
                    }else{
                        $result_harga_emas = $stmt_emas->fetch(PDO::FETCH_ASSOC);
                        $harga_emas = $result_harga_emas['harga'];
                        
                        
                        $stmt_kenaikan_gold = $this->db->prepare($sql_kenaikan_gold);
                        $stmt_kenaikan_gold->bindParam(':kategori', $ktgr);
                        if ($stmt_kenaikan_gold->execute() !== false) {
                            if($stmt_kenaikan_gold->rowCount() > 0){
                                $result_gold = $stmt_kenaikan_gold->fetch(PDO::FETCH_ASSOC);
                                
                                $ratarata_emas = $result_gold['perubahan'];
                                $status_emas = $result_gold['status'];
                                
                                if($result_gold['tanggal'] != $tgl){
                                    $id = $result_gold['id'];
                                    
                                    $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                                    if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                        $total_kenaikan = 0;
                                        foreach ($stmt_ratarata_emas as $a => $row) {
                                            if($row['status'] == '+'){
                                                $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                            }else{
                                                $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                            }
                                        }
                                        
                                        $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                        if($total_kenaikan < 0){
                                            $status_emas = $status = '-';
                                        }else{
                                            $status_emas = $status = '+';
                                        }
                                        
                                        $stmt_update_ratarata_emas = $this->db->prepare($update_ratarata_emas);
                                        $stmt_update_ratarata_emas->bindParam(':id', $id);
                                        $stmt_update_ratarata_emas->bindParam(':tgl', $tgl);
                                        $stmt_update_ratarata_emas->bindParam(':perubahan', $ratarata);
                                        $stmt_update_ratarata_emas->bindParam(':status', $status);
                                        if ($stmt_update_ratarata_emas->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                                if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                    $total_kenaikan = 0;
                                    foreach ($stmt_ratarata_emas as $a => $row) {
                                        if($row['status'] == '+'){
                                            $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                        }else{
                                            $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                        }
                                    }
                                    
                                    $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                    if($total_kenaikan < 0){
                                        $status_emas = $status = '-';
                                    }else{
                                        $status_emas = $status = '+';
                                    }
                                    
                                    $stmt_input_ratarata_emas = $this->db->prepare($input_ratarata_emas);
                                    $stmt_input_ratarata_emas->bindParam(':kategori', $ktgr);
                                    $stmt_input_ratarata_emas->bindParam(':tgl', $tgl);
                                    $stmt_input_ratarata_emas->bindParam(':perubahan', $ratarata);
                                    $stmt_input_ratarata_emas->bindParam(':status', $status);
                                    if ($stmt_input_ratarata_emas->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Emas
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $ktgr);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_emas = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_emas = $this->db->prepare($sql_kepemilikan_emas);
                        $stmt_kepemilikan_emas->bindParam(':userId', $userId);
                        $stmt_kepemilikan_emas->bindParam(':kategori', $ktgr);
                        if ($stmt_kepemilikan_emas->execute() !== false && $stmt_kepemilikan_emas->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_emas as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            $golds = $total_kepemilikan / $a;
                            
                            $diff = 0;
                            for($i = 0; $i < $a; $i++){
                                $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                $diff += $dif;
                            }
                            
                            $day = round($diff / $a);
                            $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                            if($cek_tgl > strtotime('now')){
                                $days = 0;
                                if($total_kepemilikan < 85){
                                    do{
                                        $days += $day;
                                        $total_kepemilikan += $golds;
                                    }while($total_kepemilikan < 85);
                                    
                                    $tgl_zakat = date("d-m-Y",strtotime("+".$days." days",strtotime($tgl_terakhir)));
                                    $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                    
                                    if($tgl_cek_zakat == 'belum ada'){
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $ktgr);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                if($tgl_cek_zakat != 'belum ada'){
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $ktgr);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Perak
                $url = "https://www.indogold.id/harga-emas-hari-ini";
                require_once("../util/simple_html_dom.php");
                $html = new simple_html_dom();
                $html->load_file($url);
                $element = $html->find('.Rectangle-price')[0];
                $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
                $harga_perak = preg_replace('/\D/', '', $harga_perak);
                $category = 'Perak';
                
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $category);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_perak = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_perak = $this->db->prepare($sql_kepemilikan_perak);
                        $stmt_kepemilikan_perak->bindParam(':userId', $userId);
                        $stmt_kepemilikan_perak->bindParam(':kategori', $category);
                        if ($stmt_kepemilikan_perak->execute() !== false && $stmt_kepemilikan_perak->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_perak as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            $silvers = $total_kepemilikan / $a;
                            
                            $diff = 0;
                            for($i = 0; $i < $a; $i++){
                                $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                $diff += $dif;
                            }
                            
                            $day = round($diff / $a);
                            $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                            if($cek_tgl > strtotime('now')){
                                $days = 0;
                                if($total_kepemilikan < 595){
                                    do{
                                        $days += $day;
                                        $total_kepemilikan += $silvers;
                                    }while($total_kepemilikan < 595);
                                    
                                    $tgl_zakat = date("d-m-Y",strtotime("+".$days." days",strtotime($tgl_terakhir)));
                                    $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                    
                                    if($tgl_cek_zakat == 'belum ada'){
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $category);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                if($tgl_cek_zakat != 'belum ada'){
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $category);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Saham
                $kategori = "Saham";
                $batas = ($harga_emas * 170) / 3;
                $saham = array();
                $sql_kepemilikan = "SELECT * FROM kekayaan WHERE kategori = :kategori AND user_id = :user_id ORDER BY id DESC";
                $stmt_kepemilikan = $this->db->prepare($sql_kepemilikan);
                $stmt_kepemilikan->bindParam(':kategori', $kategori);
                $stmt_kepemilikan->bindParam(':user_id', $userId);
                if($stmt_kepemilikan->execute() !== false && $stmt_kepemilikan->rowCount() > 0){
                    $result = $stmt_kepemilikan->fetchAll(PDO::FETCH_ASSOC);
                    
                    $url2 = 'http://www.floatrates.com/daily/usd.json';
                    $data2 = file_get_contents($url2);
                    $usdtoidr = json_decode($data2, true);
                    $idr = $usdtoidr['idr']['rate'];
                    
                    // Harga dan nama Saham
                    $sql_saham = "SELECT * FROM harga_saham WHERE tanggal = :tgl AND jenis = :jenis";
                    $sql_saham_last = "SELECT harga FROM harga_saham WHERE jenis = :jenis ORDER BY id DESC LIMIT 1";
                    $input_saham = "INSERT INTO harga_saham (tanggal, harga, jenis, perubahan, status, sumber) VALUES (:tanggal, :harga, :jenis, :perubahan, :status, :sumber)";
                    $input_nama_saham = "INSERT INTO nama_saham (kode, nama) VALUES (:kode, :nama)";
                
                    // Rata - rata Saham
                    $sql_kenaikan = "SELECT id, tanggal FROM kenaikan_nilai WHERE nama_item = :item";
                    $sql_ratarata = "SELECT perubahan, status FROM harga_saham WHERE jenis = :jenis";
                    $update_ratarata = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
                    $input_ratarata = "INSERT INTO kenaikan_nilai (kategori, nama_item, tanggal, perubahan, status) VALUES (:kategori, :item, :tgl, :perubahan, :status)";
                
                    // Perkiraan Zakat
                    $sql_perkiraan_saham = "SELECT id, tanggal, tgl_zakat FROM perkiraan_zakat WHERE item_id = :id";
                    $sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
                    $update_perkiraan = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                    $input_perkiraan = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat, item_id) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat, :item_id)";
                    foreach ($result as $a => $value) {
                    	$saham['kode'][$a] = $item = $value['nama_item'];
                        
                    	$stmt_saham = $this->db->prepare($sql_saham);
                        $stmt_saham->bindParam(':tgl', $tgl);
                        $stmt_saham->bindParam(':jenis', $item);
                        if ($stmt_saham->execute() !== false && $stmt_saham->rowCount() == 0) {
                            if($kondisi == 'ok'){
                                $stmt_saham_last = $this->db->prepare($sql_saham_last);
                                $stmt_saham_last->bindParam(':jenis', $item);
                                
                                $url = 'http://mboum.com/api/v1/qu/quote/?symbol='.$item.'&apikey=yMlq1FB0ATdbJnxTB8ieuzGvAsIJwoP5fqqmFAFfBiR2FGkHO7PNeHSzGGtz';
                                $data = file_get_contents($url);
                                $saham_type = json_decode($data, true);
                                $saham_price = $saham_type[0]['regularMarketPrice'];
                                $shortname = $saham_type[0]['shortName'];
                                $sumber = 'http://mboum.com/';
                
                                $saham['harga'][$item] = $harga = round($saham_price * $idr * 100);
                                
                                if ($stmt_saham_last->execute() !== false && $stmt_saham_last->rowCount() > 0) {
                                    $result_saham = $stmt_saham_last->fetch(PDO::FETCH_ASSOC);
                                    
                                    if($harga > $result_saham['harga']){
                                        $perubahan = round(($harga - $result_saham['harga']) / $result_saham['harga'],3);
                                        $status = '+';
                                    }else{
                                        $perubahan = round(($result_saham['harga'] - $harga) / $result_saham['harga'],3);
                                        $status = '-';
                                    }
                                }else{
                                    $perubahan = 0;
                                    $status = '+';
                                    
                                    $stmt_nama_saham = $this->db->prepare($input_nama_saham);
                                    $stmt_nama_saham->bindParam(':kode', $item);
                                    $stmt_nama_saham->bindParam(':nama', $shortname);
                                    if ($stmt_nama_saham->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                                
                                $stmt_saham = $this->db->prepare($input_saham);
                                $stmt_saham->bindParam(':tanggal', $tgl);
                                $stmt_saham->bindParam(':harga', $harga);
                                $stmt_saham->bindParam(':jenis', $item);
                                $stmt_saham->bindParam(':perubahan', $perubahan);
                                $stmt_saham->bindParam(':status', $status);
                                $stmt_saham->bindParam(':sumber', $sumber);
                                if ($stmt_saham->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }else{
                            $result_saham = $stmt_saham->fetch(PDO::FETCH_ASSOC);
                            $saham['harga'][$item] = $result_saham['harga'];
                            $saham['kuantitas'][$item] = $value['kuantitas'];
                            $saham['all'][$item] = $saham['kuantitas'][$item] * $saham['harga'][$item];
                            
                            $stmt_kenaikan = $this->db->prepare($sql_kenaikan);
                            $stmt_kenaikan->bindParam(':item', $item);
                            if ($stmt_kenaikan->execute() !== false && $stmt_kenaikan->rowCount() > 0) {
                                if($kondisi == 'ok'){
                                    $result_kenaikan = $stmt_kenaikan->fetch(PDO::FETCH_ASSOC);
                                    if($tgl !== $result_kenaikan['tanggal']){
                                        $id = $result_kenaikan['id'];
                                        
                                        $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                        $stmt_ratarata->bindParam(':jenis', $item);
                                        if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                            $total_kenaikan = 0;
                                            foreach ($stmt_ratarata as $b => $row) {
                                                if($row['status'] == '+'){
                                                    $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                                }else{
                                                    $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                                }
                                            }
                                            
                                            $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                            if($total_kenaikan < 0){
                                                $status = '-';
                                            }else{
                                                $status = '+';
                                            }
                                            
                                            $stmt_ratarata = $this->db->prepare($update_ratarata);
                                            $stmt_ratarata->bindParam(':id', $id);
                                            $stmt_ratarata->bindParam(':tgl', $tgl);
                                            $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                            $stmt_ratarata->bindParam(':status', $status);
                                            if ($stmt_ratarata->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                    }else{
                                        $saham['status'][$item] = $result_kenaikan['status'];
                                        $saham['ratarata'][$item] = $result_kenaikan['perubahan'];
                                        
                                        $stmt_perkiraan_saham = $this->db->prepare($sql_perkiraan_saham);
                                        $stmt_perkiraan_saham->bindParam(':id', $value['id']);
                                        if ($stmt_perkiraan_saham->execute() !== false && $stmt_perkiraan_saham->rowCount() > 0) {
                                            $result_perkiraan_saham = $stmt_perkiraan_saham->fetch(PDO::FETCH_ASSOC);
                                            if($result_perkiraan_saham['tanggal'] != $tgl){
                                                if($status_emas !== null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                                    $day = 0;
                                                    $golds = $harga_emas * 85;
                                                    do{
                                                        $day++;
                                                        
                                                        // Saham
                                                        $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                        $saham['all'][$item] += $kenaikan_saham;
                                                        
                                                        // Emas
                                                        $kenaikan_emas = $golds * $ratarata_emas;
                                                        if($status_emas == '+'){
                                                            $golds += $kenaikan_emas;
                                                        }else{
                                                            $golds -= $kenaikan_emas;
                                                        }
                                                        
                                                    }while($saham['all'][$item] < $golds);
                                                    
                                                    $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                                    $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                                    
                                                    if($result_perkiraan_saham['tgl_zakat'] !== $tgl_zakat){
                                                        $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                        $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                        $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                        $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                        $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                        if ($stmt_perkiraan->execute() == false) {
                                                            $kondisi = 'Error';
                                                        }
                                                    }
                                                }else{
                                                    $tgl_zakat = $nilai_zakat = null;
                                                    
                                                    $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                    $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                    $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                    $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                    $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                    if ($stmt_perkiraan->execute() == false) {
                                                        $kondisi = 'Error';
                                                    } 
                                                }
                                            }
                                        }else{
                                            if($status_emas != null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                                $day = 0;
                                                $golds = $harga_emas * 85;
                                                do{
                                                    $day++;
                                                    
                                                    // Saham
                                                    $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                    $saham['all'][$item] += $kenaikan_saham;
                                                    
                                                    // Emas
                                                    $kenaikan_emas = $golds * $ratarata_emas;
                                                    if($status_emas == '+'){
                                                        $golds += $kenaikan_emas;
                                                    }else{
                                                        $golds -= $kenaikan_emas;
                                                    }
                                                    
                                                }while($saham['all'][$item] < $golds);
                                                
                                                $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                                $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                                
                                                $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                                $stmt_perkiraan->bindParam(':userId', $userId);
                                                $stmt_perkiraan->bindParam(':kategori', $kategori);
                                                $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                                if ($stmt_perkiraan->execute() == false) {
                                                    $kondisi = 'Error';
                                                }else{
                                                	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
    							                    $stmt_nama_saham->bindParam(':kode', $item);
    							                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
    							                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
                                                		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
                                                		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                                		$stmt_notif->bindParam(':user_id', $userId);
    					                                $stmt_notif->bindParam(':notifikasi', $message);
    					                                if($stmt_notif->execute() == false) {
    					                                	$kondisi = 'Error';
    					                                }
    							                    }
                                                }
                                            }else{
                                                $tgl_zakat = $nilai_zakat = null;
                                                
                                                $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                                $stmt_perkiraan->bindParam(':userId', $userId);
                                                $stmt_perkiraan->bindParam(':kategori', $kategori);
                                                $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                                if ($stmt_perkiraan->execute() == false) {
                                                    $kondisi = 'Error';
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{
                                if($kondisi == 'ok'){
                                    $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                    $stmt_ratarata->bindParam(':jenis', $item);
                                    if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                        $total_kenaikan = 0;
                                        foreach ($stmt_ratarata as $b => $row) {
                                            if($row['status'] == '+'){
                                                $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                            }else{
                                                $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                            }
                                        }
                                        
                                        $saham['ratarata'][$item] = $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                        if($total_kenaikan < 0){
                                            $saham['status'][$item] = $status = '-';
                                        }else{
                                            $saham['status'][$item] = $status = '+';
                                        }
                                        
                                        $stmt_ratarata = $this->db->prepare($input_ratarata);
                                        $stmt_ratarata->bindParam(':kategori', $kategori);
                                        $stmt_ratarata->bindParam(':item', $item);
                                        $stmt_ratarata->bindParam(':tgl', $tgl);
                                        $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                        $stmt_ratarata->bindParam(':status', $status);
                                        if ($stmt_ratarata->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                } 
                            }
                        }
                    }
                }
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

    $app->get('/home/cek', function($request, $response, $args){
        /*$url = "https://www.indogold.id/harga-emas-hari-ini";
        require_once("../util/simple_html_dom.php");
        $html = new simple_html_dom();
        $html->load_file($url);
        $element = $html->find('.Rectangle-price')[0];
        $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
        $harga_perak = preg_replace('/\D/', '', $harga_perak);*/
        $curl = curl_init();
        
        curl_setopt_array($curl, [
        	CURLOPT_URL => "https://apidojo-yahoo-finance-v1.p.rapidapi.com/stock/v2/get-financials?symbol=ANTM&region=IN",
        	CURLOPT_RETURNTRANSFER => true,
        	CURLOPT_FOLLOWLOCATION => true,
        	CURLOPT_ENCODING => "",
        	CURLOPT_MAXREDIRS => 10,
        	CURLOPT_TIMEOUT => 30,
        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        	CURLOPT_CUSTOMREQUEST => "GET",
        	CURLOPT_HTTPHEADER => [
        		"x-rapidapi-host: apidojo-yahoo-finance-v1.p.rapidapi.com",
        		"x-rapidapi-key: d1d86deb2dmshf60a90bcea56ad8p1051dcjsn506419b48992"
        	],
        ]);
        
        $data = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        return $response->withJson($data, 200);
    });

    $app->get('/home/banner', function ($request, $response, $args) {
        require_once("../util/simple_html_dom.php");

        $html = new simple_html_dom();
        $html->load_file('https://zakatsukses.org/');

        $arr_img_url = array();

        foreach ($html->find('.swiper-slide-image') as $elements) {
            array_push($arr_img_url, ["banner_url" => $elements->src]);
        }
        
        $tgl = date("d-m-Y");
        $ktgr = "Emas";
        $kondisi = 'ok';
        $status_emas = '';
        $sql_emas = "SELECT id, harga FROM harga_emas WHERE tanggal = :tgl";
        $stmt_emas = $this->db->prepare($sql_emas);
        $stmt_emas->bindParam(':tgl', $tgl);
        if ($stmt_emas->execute() !== false) {
            
            // Harga Emas
            $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
            $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
        
            // Rata - rata emas
            $sql_kenaikan_gold = "SELECT id, tanggal, perubahan, status FROM kenaikan_nilai WHERE kategori = :kategori";
            $sql_ratarata_emas = "SELECT perubahan, status FROM harga_emas";
            $update_ratarata_emas = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
            $input_ratarata_emas = "INSERT INTO kenaikan_nilai (kategori, tanggal, perubahan, status) VALUES (:kategori, :tgl, :perubahan, :status)";
        
            if($stmt_emas->rowCount() == 0){
        
            	// Harga Emas{
                $stmt_emas_last = $this->db->prepare($sql_emas_last);
                if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                    $result_emas = $stmt_emas_last->fetch(PDO::FETCH_ASSOC);
                    
                    $url = "https://www.indogold.id/harga-emas-hari-ini";
                    
                    require_once("../util/simple_html_dom.php");
        
                    $html = new simple_html_dom();
                    $html->load_file($url);
        
                    $element = $html->find('.Rectangle-price')[0];
                    $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                    $harga_emas = preg_replace('/\D/', '', $harga_emas);
                    
                    if($harga_emas > $result_emas['harga']){
                        $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                        $status = '+';
                    }else{
                        $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                        $status = '-';
                    }
                    
                    $stmt_input_emas = $this->db->prepare($input_emas);
                    $stmt_input_emas->bindParam(':tanggal', $tgl);
                    $stmt_input_emas->bindParam(':harga', $harga_emas);
                    $stmt_input_emas->bindParam(':perubahan', $perubahan);
                    $stmt_input_emas->bindParam(':status', $status);
                    $stmt_input_emas->bindParam(':sumber', $url);
        
                    if ($stmt_input_emas->execute() == false) {
                        $kondisi = "Error";
                    }
                }
                // Harga Emas }
        
            }else{
            	$result_emas = $stmt_emas->fetch(PDO::FETCH_ASSOC);
            	$harga_emas = $result_emas['harga'];
        
            	// Rata - rata emas {
                $stmt_kenaikan_gold = $this->db->prepare($sql_kenaikan_gold);
                $stmt_kenaikan_gold->bindParam(':kategori', $ktgr);
                if ($stmt_kenaikan_gold->execute() !== false) {
                    if($stmt_kenaikan_gold->rowCount() > 0){
                        $result_gold = $stmt_kenaikan_gold->fetch(PDO::FETCH_ASSOC);
                        $status_emas = $result_gold['status'];
                        $ratarata_emas = $result_gold['perubahan'];
        
                        if($result_gold['tanggal'] != $tgl){
                            $id = $result_gold['id'];
                            
                            $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                            if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                $total_kenaikan = 0;
                                foreach ($stmt_ratarata_emas as $a => $row) {
                                    if($row['status'] == '+'){
                                        $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                    }else{
                                        $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                    }
                                }
                                
                                $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                if($total_kenaikan < 0){
                                    $status_emas = $status = '-';
                                }else{
                                    $status_emas = $status = '+';
                                }
                                
                                $stmt_update_ratarata_emas = $this->db->prepare($update_ratarata_emas);
                                $stmt_update_ratarata_emas->bindParam(':id', $id);
                                $stmt_update_ratarata_emas->bindParam(':tgl', $tgl);
                                $stmt_update_ratarata_emas->bindParam(':perubahan', $ratarata);
                                $stmt_update_ratarata_emas->bindParam(':status', $status);
                                if ($stmt_update_ratarata_emas->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }
                    }else{
                        $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                        if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                            $total_kenaikan = 0;
                            foreach ($stmt_ratarata_emas as $a => $row) {
                                if($row['status'] == '+'){
                                    $total_kenaikan += $row['perubahan'];
                                }else{
                                    $total_kenaikan -= $row['perubahan'];
                                }
                            }
                            
                            $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                            if($total_kenaikan < 0){
                                $status_emas = $status = '-';
                            }else{
                                $status_emas = $status = '+';
                            }
                            
                            $stmt_input_ratarata_emas = $this->db->prepare($input_ratarata_emas);
                            $stmt_input_ratarata_emas->bindParam(':kategori', $ktgr);
                            $stmt_input_ratarata_emas->bindParam(':tgl', $tgl);
                            $stmt_input_ratarata_emas->bindParam(':perubahan', $ratarata);
                            $stmt_input_ratarata_emas->bindParam(':status', $status);
                            if ($stmt_input_ratarata_emas->execute() == false) {
                                $kondisi = 'Error';
                            }
                        }
                    }
                }
                // Rata - rata emas
            }
        }
        
        $sql_user = "SELECT * FROM user";
        $stmt_user = $this->db->prepare($sql_user);
        if ($stmt_user->execute() !== false && $stmt_user->rowCount() > 0) {
            foreach ($stmt_user as $d => $value) {
                $userId = $value['user_id'];
                
                // Emas
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $ktgr);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_emas = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_emas = $this->db->prepare($sql_kepemilikan_emas);
                        $stmt_kepemilikan_emas->bindParam(':userId', $userId);
                        $stmt_kepemilikan_emas->bindParam(':kategori', $ktgr);
                        if ($stmt_kepemilikan_emas->execute() !== false && $stmt_kepemilikan_emas->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_emas as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            
                            if($total_kepemilikan < 85){
                                $golds = $total_kepemilikan / ( $a - 1 );
                                
                                $diff = 0;
                                for($i = 0; $i < $a; $i++){
                                    $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                    $diff += $dif;
                                }
                                
                                $day = round($diff / $a);
                                $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                                if($cek_tgl > strtotime('now')){
                                    $days = 0;
                                    if($total_kepemilikan < 85){
                                        do{
                                            $days += $day;
                                            $total_kepemilikan += $golds;
                                        }while($total_kepemilikan < 85);
                                        
                                        $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                        $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                        
                                        $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                        
                                        if($tgl_cek_zakat == 'belum ada'){
                                            $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                            $stmt_zakat = $this->db->prepare($input_zakat);
                                            $stmt_zakat->bindParam(':userId', $userId);
                                            $stmt_zakat->bindParam(':kategori', $ktgr);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }else{
                                            $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                            $stmt_zakat = $this->db->prepare($update_zakat);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            $stmt_zakat->bindParam(':id', $id);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                        
                                        if($kondisi !== 'Error'){
                                            $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                    		$stmt_notif->bindParam(':user_id', $userId);
        	                                $stmt_notif->bindParam(':notifikasi', $message);
        	                                if($stmt_notif->execute() == false) {
        	                                    $kondisi = 'Error';
        	                                }
                                        }
                                    }
                                }else{
                                    if($tgl_cek_zakat != 'belum ada'){
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $ktgr);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                rsort($urutan);
                                $now = strtotime('now');
                                
                                $tgl_zakat = strtotime("+12 month",$urutan[0]);
                                if($tgl_zakat < $now){
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                }
                                
                                $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                
                                if($tgl_cek_zakat == 'belum ada'){
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $ktgr);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                                
                                if($kondisi !== 'Error'){
                                    $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() == false) {
	                                    $kondisi = 'Error';
	                                }
                                }
                            }
                        }
                    }
                }
                
                // Perak
                $url = "https://www.indogold.id/harga-emas-hari-ini";
                require_once("../util/simple_html_dom.php");
                $html = new simple_html_dom();
                $html->load_file($url);
                $element = $html->find('.Rectangle-price')[0];
                $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
                $harga_perak = preg_replace('/\D/', '', $harga_perak);
                $category = 'Perak';
                
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $category);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_perak = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_perak = $this->db->prepare($sql_kepemilikan_perak);
                        $stmt_kepemilikan_perak->bindParam(':userId', $userId);
                        $stmt_kepemilikan_perak->bindParam(':kategori', $category);
                        if ($stmt_kepemilikan_perak->execute() !== false && $stmt_kepemilikan_perak->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_perak as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            
                            if($total_kepemilikan < 595){
                                $silvers = $total_kepemilikan / ( $a - 1 );
                                
                                $diff = 0;
                                for($i = 0; $i < $a; $i++){
                                    $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                    $diff += $dif;
                                }
                                
                                $day = round($diff / $a);
                                $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                                if($cek_tgl > strtotime('now')){
                                    $days = 0;
                                    if($total_kepemilikan < 595){
                                        do{
                                            $days += $day;
                                            $total_kepemilikan += $silvers;
                                        }while($total_kepemilikan < 595);
                                        
                                        $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                        $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                        
                                        $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                        
                                        if($tgl_cek_zakat == 'belum ada'){
                                            $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                            $stmt_zakat = $this->db->prepare($input_zakat);
                                            $stmt_zakat->bindParam(':userId', $userId);
                                            $stmt_zakat->bindParam(':kategori', $category);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }else{
                                            $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                            $stmt_zakat = $this->db->prepare($update_zakat);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            $stmt_zakat->bindParam(':id', $id);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                        
                                        if($kondisi !== 'Error'){
                                            $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                    		$stmt_notif->bindParam(':user_id', $userId);
        	                                $stmt_notif->bindParam(':notifikasi', $message);
        	                                if($stmt_notif->execute() == false) {
        	                                    $kondisi = 'Error';
        	                                }
                                        }
                                    }
                                }else{
                                    if($tgl_cek_zakat != 'belum ada'){
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $category);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                rsort($urutan);
                                $now = strtotime('now');
                                
                                $tgl_zakat = strtotime("+12 month",$urutan[0]);
                                if($tgl_zakat < $now){
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                }
                                
                                $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                
                                if($tgl_cek_zakat == 'belum ada'){
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $category);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                                
                                if($kondisi !== 'Error'){
                                    $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() == false) {
	                                    $kondisi = 'Error';
	                                }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Saham
        $kategori = "Saham";
        $batas = ($harga_emas * 170) / 3;
        $saham = array();
        $sql_kepemilikan = "SELECT * FROM kekayaan WHERE kategori = :kategori GROUP BY nama_item";
        $stmt_kepemilikan = $this->db->prepare($sql_kepemilikan);
        $stmt_kepemilikan->bindParam(':kategori', $kategori);
        if ($stmt_kepemilikan->execute() !== false && $stmt_kepemilikan->rowCount() > 0) {
            
            $url2 = 'http://www.floatrates.com/daily/usd.json';
            $data2 = file_get_contents($url2);
            $usdtoidr = json_decode($data2, true);
            $idr = $usdtoidr['idr']['rate'];
            
            // Harga dan nama Saham
            $sql_saham = "SELECT * FROM harga_saham WHERE tanggal = :tgl AND jenis = :jenis";
            $sql_saham_last = "SELECT harga FROM harga_saham WHERE jenis = :jenis ORDER BY id DESC LIMIT 1";
            $input_saham = "INSERT INTO harga_saham (tanggal, harga, jenis, perubahan, status, sumber) VALUES (:tanggal, :harga, :jenis, :perubahan, :status, :sumber)";
            $input_nama_saham = "INSERT INTO nama_saham (kode, nama) VALUES (:kode, :nama)";
        
            // Rata - rata Saham
            $sql_kenaikan = "SELECT id, tanggal FROM kenaikan_nilai WHERE nama_item = :item";
            $sql_ratarata = "SELECT perubahan, status FROM harga_saham WHERE jenis = :jenis";
            $update_ratarata = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
            $input_ratarata = "INSERT INTO kenaikan_nilai (kategori, nama_item, tanggal, perubahan, status) VALUES (:kategori, :item, :tgl, :perubahan, :status)";
        
            // Perkiraan Zakat
            $sql_perkiraan_saham = "SELECT id, tanggal, tgl_zakat FROM perkiraan_zakat WHERE item_id = :id";
            $sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
            $update_perkiraan = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
            $input_perkiraan = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat, item_id) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat, :item_id)";
        
            foreach ($stmt_kepemilikan as $a => $value) {
                $saham['kode'][$a] = $item = $value['nama_item'];
                $userId = $value['user_id'];
                
                // Harga dan nama Saham {
                $stmt_saham = $this->db->prepare($sql_saham);
                $stmt_saham->bindParam(':tgl', $tgl);
                $stmt_saham->bindParam(':jenis', $item);
                if ($stmt_saham->execute() !== false && $stmt_saham->rowCount() == 0) {
                    if($kondisi == 'ok'){
                        $stmt_saham_last = $this->db->prepare($sql_saham_last);
                        $stmt_saham_last->bindParam(':jenis', $item);
                        
                        $url = 'http://mboum.com/api/v1/qu/quote/?symbol='.$item.'&apikey=yMlq1FB0ATdbJnxTB8ieuzGvAsIJwoP5fqqmFAFfBiR2FGkHO7PNeHSzGGtz';
                        $data = file_get_contents($url);
                        $saham_type = json_decode($data, true);
                        $saham_price = $saham_type[0]['regularMarketPrice'];
                        $shortname = $saham_type[0]['shortName'];
                        $sumber = 'http://mboum.com/';
        
                        $harga = round($saham_price * $idr * 100);
                        
                        if ($stmt_saham_last->execute() !== false && $stmt_saham_last->rowCount() > 0) {
                            $result_saham = $stmt_saham_last->fetch(PDO::FETCH_ASSOC);
                            
                            if($harga > $result_saham['harga']){
                                $perubahan = round(($harga - $result_saham['harga']) / $result_saham['harga'],3);
                                $status = '+';
                            }else{
                                $perubahan = round(($result_saham['harga'] - $harga) / $result_saham['harga'],3);
                                $status = '-';
                            }
                        }else{
                            $perubahan = 0;
                            $status = '+';
                            
                            $stmt_nama_saham = $this->db->prepare($input_nama_saham);
                            $stmt_nama_saham->bindParam(':kode', $item);
                            $stmt_nama_saham->bindParam(':nama', $shortname);
                            if ($stmt_nama_saham->execute() == false) {
                                $kondisi = 'Error';
                            }
                        }
                        
                        $stmt_saham = $this->db->prepare($input_saham);
                        $stmt_saham->bindParam(':tanggal', $tgl);
                        $stmt_saham->bindParam(':harga', $harga);
                        $stmt_saham->bindParam(':jenis', $item);
                        $stmt_saham->bindParam(':perubahan', $perubahan);
                        $stmt_saham->bindParam(':status', $status);
                        $stmt_saham->bindParam(':sumber', $sumber);
                        if ($stmt_saham->execute() == false) {
                            $kondisi = 'Error';
                        }
                    }
        
                // Harga dan nama Saham }
        
                }else{
                	$result_saham = $stmt_saham->fetch(PDO::FETCH_ASSOC);
                    $saham['harga'][$item] = $result_saham['harga'];
                    $saham['kuantitas'][$item] = $value['kuantitas'];
                    $saham['all'][$item] = $saham['kuantitas'][$item] * $saham['harga'][$item];
        
        
                	// Rata - rata Saham {
                    $stmt_kenaikan = $this->db->prepare($sql_kenaikan);
                    $stmt_kenaikan->bindParam(':item', $item);
                    if ($stmt_kenaikan->execute() !== false && $stmt_kenaikan->rowCount() > 0) {
                        if($kondisi == 'ok'){
                            $result_kenaikan = $stmt_kenaikan->fetch(PDO::FETCH_ASSOC);
                            if($tgl !== $result_kenaikan['tanggal']){
                                $id = $result_kenaikan['id'];
                                
                                $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                $stmt_ratarata->bindParam(':jenis', $item);
                                if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                    $total_kenaikan = 0;
                                    foreach ($stmt_ratarata as $b => $row) {
                                        if($row['status'] == '+'){
                                            $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                        }else{
                                            $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                        }
                                    }
                                    
                                    $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                    if($total_kenaikan < 0){
                                        $status = '-';
                                    }else{
                                        $status = '+';
                                    }
                                    
                                    $stmt_ratarata = $this->db->prepare($update_ratarata);
                                    $stmt_ratarata->bindParam(':id', $id);
                                    $stmt_ratarata->bindParam(':tgl', $tgl);
                                    $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                    $stmt_ratarata->bindParam(':status', $status);
                                    if ($stmt_ratarata->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }else{
                                $saham['status'][$item] = $result_kenaikan['status'];
                                $saham['ratarata'][$item] = $result_kenaikan['perubahan'];
                                
                                // Perkiraan zakat {
                                $stmt_perkiraan_saham = $this->db->prepare($sql_perkiraan_saham);
                                $stmt_perkiraan_saham->bindParam(':id', $value['id']);
                                if ($stmt_perkiraan_saham->execute() !== false && $stmt_perkiraan_saham->rowCount() > 0) {
                                    $result_perkiraan_saham = $stmt_perkiraan_saham->fetch(PDO::FETCH_ASSOC);
                                    if($result_perkiraan_saham['tanggal'] != $tgl){
                                        if($status_emas !== null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                            $day = 0;
                                            $golds = $harga_emas * 85;
                                            do{
                                                $day++;
                                                
                                                // Saham
                                                $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                $saham['all'][$item] += $kenaikan_saham;
                                                
                                                // Emas
                                                $kenaikan_emas = $golds * $ratarata_emas;
                                                if($status_emas == '+'){
                                                    $golds += $kenaikan_emas;
                                                }else{
                                                    $golds -= $kenaikan_emas;
                                                }
                                                
                                            }while($saham['all'][$item] < $golds);
                                            
                                            $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                            $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                            
                                            if($result_perkiraan_saham['tgl_zakat'] !== $tgl_zakat){
                                                $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                if ($stmt_perkiraan->execute() == false) {
                                                    $kondisi = 'Error';
                                                }else{
        	                                    	$sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
        	                                    	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
        						                    $stmt_nama_saham->bindParam(':kode', $item);
        						                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
        						                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
        	                                    		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
        	                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
        	                                    		$stmt_notif->bindParam(':user_id', $userId);
        				                                $stmt_notif->bindParam(':notifikasi', $message);
        				                                if($stmt_notif->execute() == false) {
        				                                	$kondisi = 'Error';
        				                                }
        				                            }
        	                                    }
                                            }
                                        }else{
                                            $tgl_zakat = $nilai_zakat = null;
                                            
                                            $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                            $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                            $stmt_perkiraan->bindParam(':tgl', $tgl);
                                            $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                            $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_perkiraan->execute() == false) {
                                                $kondisi = 'Error';
                                            } 
                                        }
                                    }
                                }else{
                                    if($status_emas != null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                        $day = 0;
                                        $golds = $harga_emas * 85;
                                        do{
                                            $day++;
                                            
                                            // Saham
                                            $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                            $saham['all'][$item] += $kenaikan_saham;
                                            
                                            // Emas
                                            $kenaikan_emas = $golds * $ratarata_emas;
                                            if($status_emas == '+'){
                                                $golds += $kenaikan_emas;
                                            }else{
                                                $golds -= $kenaikan_emas;
                                            }
                                            
                                        }while($saham['all'][$item] < $golds);
                                        
                                        $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                        $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                        
                                        $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                        $stmt_perkiraan->bindParam(':userId', $userId);
                                        $stmt_perkiraan->bindParam(':kategori', $kategori);
                                        $stmt_perkiraan->bindParam(':tgl', $tgl);
                                        $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                        $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                        if ($stmt_perkiraan->execute() == false) {
                                            $kondisi = 'Error';
                                        }else{
                                        	$sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
                                        	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
    					                    $stmt_nama_saham->bindParam(':kode', $item);
    					                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
    					                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
                                        		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
                                        		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                        		$stmt_notif->bindParam(':user_id', $userId);
    			                                $stmt_notif->bindParam(':notifikasi', $message);
    			                                if($stmt_notif->execute() == false) {
    			                                	$kondisi = 'Error';
    			                                }
    			                            }
                                        }
                                    }else{
                                        $tgl_zakat = $nilai_zakat = null;
                                        
                                        $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                        $stmt_perkiraan->bindParam(':userId', $userId);
                                        $stmt_perkiraan->bindParam(':kategori', $kategori);
                                        $stmt_perkiraan->bindParam(':tgl', $tgl);
                                        $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                        $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                        if ($stmt_perkiraan->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                                // Perkiraan zakat }
                            }
                        }
                    }else{
                       if($kondisi == 'ok'){
                            $stmt_ratarata = $this->db->prepare($sql_ratarata);
                            $stmt_ratarata->bindParam(':jenis', $item);
                            if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                $total_kenaikan = 0;
                                foreach ($stmt_ratarata as $b => $row) {
                                    if($row['status'] == '+'){
                                        $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                    }else{
                                        $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                    }
                                }
                                
                                $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                if($total_kenaikan < 0){
                                    $status = '-';
                                }else{
                                    $status = '+';
                                }
                                
                                $stmt_ratarata = $this->db->prepare($input_ratarata);
                                $stmt_ratarata->bindParam(':kategori', $kategori);
                                $stmt_ratarata->bindParam(':item', $item);
                                $stmt_ratarata->bindParam(':tgl', $tgl);
                                $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                $stmt_ratarata->bindParam(':status', $status);
                                if ($stmt_ratarata->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        } 
                    }
                    // Rata - rata Saham }
        
                }
            }
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
        
        $tgl = date("d-m-Y");
        $ktgr = "Emas";
        $kondisi = 'ok';
        $status_emas = '';
        $sql_emas = "SELECT id, harga FROM harga_emas WHERE tanggal = :tgl";
        $stmt_emas = $this->db->prepare($sql_emas);
        $stmt_emas->bindParam(':tgl', $tgl);
        if ($stmt_emas->execute() !== false) {
            
            // Harga Emas
            $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
            $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
        
            // Rata - rata emas
            $sql_kenaikan_gold = "SELECT id, tanggal, perubahan, status FROM kenaikan_nilai WHERE kategori = :kategori";
            $sql_ratarata_emas = "SELECT perubahan, status FROM harga_emas";
            $update_ratarata_emas = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
            $input_ratarata_emas = "INSERT INTO kenaikan_nilai (kategori, tanggal, perubahan, status) VALUES (:kategori, :tgl, :perubahan, :status)";
        
            if($stmt_emas->rowCount() == 0){
        
            	// Harga Emas{
                $stmt_emas_last = $this->db->prepare($sql_emas_last);
                if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                    $result_emas = $stmt_emas_last->fetch(PDO::FETCH_ASSOC);
                    
                    $url = "https://www.indogold.id/harga-emas-hari-ini";
                    
                    require_once("../util/simple_html_dom.php");
        
                    $html = new simple_html_dom();
                    $html->load_file($url);
        
                    $element = $html->find('.Rectangle-price')[0];
                    $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                    $harga_emas = preg_replace('/\D/', '', $harga_emas);
                    
                    if($harga_emas > $result_emas['harga']){
                        $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                        $status = '+';
                    }else{
                        $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                        $status = '-';
                    }
                    
                    $stmt_input_emas = $this->db->prepare($input_emas);
                    $stmt_input_emas->bindParam(':tanggal', $tgl);
                    $stmt_input_emas->bindParam(':harga', $harga_emas);
                    $stmt_input_emas->bindParam(':perubahan', $perubahan);
                    $stmt_input_emas->bindParam(':status', $status);
                    $stmt_input_emas->bindParam(':sumber', $url);
        
                    if ($stmt_input_emas->execute() == false) {
                        $kondisi = "Error";
                    }
                }
                // Harga Emas }
        
            }else{
            	$result_emas = $stmt_emas->fetch(PDO::FETCH_ASSOC);
            	$harga_emas = $result_emas['harga'];
        
            	// Rata - rata emas {
                $stmt_kenaikan_gold = $this->db->prepare($sql_kenaikan_gold);
                $stmt_kenaikan_gold->bindParam(':kategori', $ktgr);
                if ($stmt_kenaikan_gold->execute() !== false) {
                    if($stmt_kenaikan_gold->rowCount() > 0){
                        $result_gold = $stmt_kenaikan_gold->fetch(PDO::FETCH_ASSOC);
                        $status_emas = $result_gold['status'];
                        $ratarata_emas = $result_gold['perubahan'];
        
                        if($result_gold['tanggal'] != $tgl){
                            $id = $result_gold['id'];
                            
                            $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                            if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                $total_kenaikan = 0;
                                foreach ($stmt_ratarata_emas as $a => $row) {
                                    if($row['status'] == '+'){
                                        $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                    }else{
                                        $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                    }
                                }
                                
                                $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                if($total_kenaikan < 0){
                                    $status_emas = $status = '-';
                                }else{
                                    $status_emas = $status = '+';
                                }
                                
                                $stmt_update_ratarata_emas = $this->db->prepare($update_ratarata_emas);
                                $stmt_update_ratarata_emas->bindParam(':id', $id);
                                $stmt_update_ratarata_emas->bindParam(':tgl', $tgl);
                                $stmt_update_ratarata_emas->bindParam(':perubahan', $ratarata);
                                $stmt_update_ratarata_emas->bindParam(':status', $status);
                                if ($stmt_update_ratarata_emas->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }
                    }else{
                        $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                        if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                            $total_kenaikan = 0;
                            foreach ($stmt_ratarata_emas as $a => $row) {
                                if($row['status'] == '+'){
                                    $total_kenaikan += $row['perubahan'];
                                }else{
                                    $total_kenaikan -= $row['perubahan'];
                                }
                            }
                            
                            $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                            if($total_kenaikan < 0){
                                $status_emas = $status = '-';
                            }else{
                                $status_emas = $status = '+';
                            }
                            
                            $stmt_input_ratarata_emas = $this->db->prepare($input_ratarata_emas);
                            $stmt_input_ratarata_emas->bindParam(':kategori', $ktgr);
                            $stmt_input_ratarata_emas->bindParam(':tgl', $tgl);
                            $stmt_input_ratarata_emas->bindParam(':perubahan', $ratarata);
                            $stmt_input_ratarata_emas->bindParam(':status', $status);
                            if ($stmt_input_ratarata_emas->execute() == false) {
                                $kondisi = 'Error';
                            }
                        }
                    }
                }
                // Rata - rata emas
            }
        }
        
        $sql_user = "SELECT * FROM user";
        $stmt_user = $this->db->prepare($sql_user);
        if ($stmt_user->execute() !== false && $stmt_user->rowCount() > 0) {
            foreach ($stmt_user as $d => $value) {
                $userId = $value['user_id'];
                
                // Emas
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $ktgr);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_emas = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_emas = $this->db->prepare($sql_kepemilikan_emas);
                        $stmt_kepemilikan_emas->bindParam(':userId', $userId);
                        $stmt_kepemilikan_emas->bindParam(':kategori', $ktgr);
                        if ($stmt_kepemilikan_emas->execute() !== false && $stmt_kepemilikan_emas->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_emas as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            
                            if($total_kepemilikan < 85){
                                $golds = $total_kepemilikan / ( $a - 1 );
                                
                                $diff = 0;
                                for($i = 0; $i < $a; $i++){
                                    $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                    $diff += $dif;
                                }
                                
                                $day = round($diff / $a);
                                $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                                if($cek_tgl > strtotime('now')){
                                    $days = 0;
                                    if($total_kepemilikan < 85){
                                        do{
                                            $days += $day;
                                            $total_kepemilikan += $golds;
                                        }while($total_kepemilikan < 85);
                                        
                                        $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                        $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                        
                                        $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                        
                                        if($tgl_cek_zakat == 'belum ada'){
                                            $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                            $stmt_zakat = $this->db->prepare($input_zakat);
                                            $stmt_zakat->bindParam(':userId', $userId);
                                            $stmt_zakat->bindParam(':kategori', $ktgr);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }else{
                                            $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                            $stmt_zakat = $this->db->prepare($update_zakat);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            $stmt_zakat->bindParam(':id', $id);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                        
                                        if($kondisi !== 'Error'){
                                            $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                    		$stmt_notif->bindParam(':user_id', $userId);
        	                                $stmt_notif->bindParam(':notifikasi', $message);
        	                                if($stmt_notif->execute() == false) {
        	                                    $kondisi = 'Error';
        	                                }
                                        }
                                    }
                                }else{
                                    if($tgl_cek_zakat != 'belum ada'){
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $ktgr);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                rsort($urutan);
                                $now = strtotime('now');
                                
                                $tgl_zakat = strtotime("+12 month",$urutan[0]);
                                if($tgl_zakat < $now){
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                }
                                
                                $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                
                                if($tgl_cek_zakat == 'belum ada'){
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $ktgr);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                                
                                if($kondisi !== 'Error'){
                                    $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() == false) {
	                                    $kondisi = 'Error';
	                                }
                                }
                            }
                        }
                    }
                }
                
                // Perak
                $url = "https://www.indogold.id/harga-emas-hari-ini";
                require_once("../util/simple_html_dom.php");
                $html = new simple_html_dom();
                $html->load_file($url);
                $element = $html->find('.Rectangle-price')[0];
                $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
                $harga_perak = preg_replace('/\D/', '', $harga_perak);
                $category = 'Perak';
                
                $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                $stmt_cek = $this->db->prepare($sql_cek);
                $stmt_cek->bindParam(':userId', $userId);
                $stmt_cek->bindParam(':kategori', $category);
                if ($stmt_cek->execute() !== false) {
                    if($stmt_cek->rowCount() > 0){
                        $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                        $id = $result_cek['id'];
                        $tgl_cek_zakat = $result_cek['tgl_zakat'];
                        $tgl_cek = $result_cek['tanggal'];
                    }else{
                        $tgl_cek_zakat = 'belum ada';
                        $tgl_cek = null;
                    }
                    
                    if($tgl_cek != $tgl){
                        $sql_kepemilikan_perak = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                        $stmt_kepemilikan_perak = $this->db->prepare($sql_kepemilikan_perak);
                        $stmt_kepemilikan_perak->bindParam(':userId', $userId);
                        $stmt_kepemilikan_perak->bindParam(':kategori', $category);
                        if ($stmt_kepemilikan_perak->execute() !== false && $stmt_kepemilikan_perak->rowCount() > 1) {
                            $urutan = array();
                            $total_kepemilikan = 0;
                            $tgl_terakhir = '';
                            foreach ($stmt_kepemilikan_perak as $a => $val) {
                                $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                                $total_kepemilikan += $val['kuantitas'];
                                $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                            }
                            sort($urutan);
                            
                            if($total_kepemilikan < 595){
                                $silvers = $total_kepemilikan / ( $a - 1 );
                                
                                $diff = 0;
                                for($i = 0; $i < $a; $i++){
                                    $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                    $diff += $dif;
                                }
                                
                                $day = round($diff / $a);
                                $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                                if($cek_tgl > strtotime('now')){
                                    $days = 0;
                                    if($total_kepemilikan < 595){
                                        do{
                                            $days += $day;
                                            $total_kepemilikan += $silvers;
                                        }while($total_kepemilikan < 595);
                                        
                                        $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                        $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                        
                                        $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                        
                                        if($tgl_cek_zakat == 'belum ada'){
                                            $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                            $stmt_zakat = $this->db->prepare($input_zakat);
                                            $stmt_zakat->bindParam(':userId', $userId);
                                            $stmt_zakat->bindParam(':kategori', $category);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }else{
                                            $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                            $stmt_zakat = $this->db->prepare($update_zakat);
                                            $stmt_zakat->bindParam(':tgl', $tgl);
                                            $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                            $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                            $stmt_zakat->bindParam(':id', $id);
                                            if ($stmt_zakat->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                        
                                        if($kondisi !== 'Error'){
                                            $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                    		$stmt_notif->bindParam(':user_id', $userId);
        	                                $stmt_notif->bindParam(':notifikasi', $message);
        	                                if($stmt_notif->execute() == false) {
        	                                    $kondisi = 'Error';
        	                                }
                                        }
                                    }
                                }else{
                                    if($tgl_cek_zakat != 'belum ada'){
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $tgl_zakat = null;
                                        $nilai_zakat = null;
                                        
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $category);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                            }else{
                                rsort($urutan);
                                $now = strtotime('now');
                                
                                $tgl_zakat = strtotime("+12 month",$urutan[0]);
                                if($tgl_zakat < $now){
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                }
                                
                                $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                
                                if($tgl_cek_zakat == 'belum ada'){
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $category);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                                
                                if($kondisi !== 'Error'){
                                    $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() == false) {
	                                    $kondisi = 'Error';
	                                }
                                }
                            }
                        }
                    }
                }
                
            }
        }
        
        // Saham
        $kategori = "Saham";
        $batas = ($harga_emas * 170) / 3;
        $saham = array();
        $sql_kepemilikan = "SELECT * FROM kekayaan WHERE kategori = :kategori GROUP BY nama_item";
        $stmt_kepemilikan = $this->db->prepare($sql_kepemilikan);
        $stmt_kepemilikan->bindParam(':kategori', $kategori);
        if ($stmt_kepemilikan->execute() !== false && $stmt_kepemilikan->rowCount() > 0) {
            
            $url2 = 'http://www.floatrates.com/daily/usd.json';
            $data2 = file_get_contents($url2);
            $usdtoidr = json_decode($data2, true);
            $idr = $usdtoidr['idr']['rate'];
            
            // Harga dan nama Saham
            $sql_saham = "SELECT * FROM harga_saham WHERE tanggal = :tgl AND jenis = :jenis";
            $sql_saham_last = "SELECT harga FROM harga_saham WHERE jenis = :jenis ORDER BY id DESC LIMIT 1";
            $input_saham = "INSERT INTO harga_saham (tanggal, harga, jenis, perubahan, status, sumber) VALUES (:tanggal, :harga, :jenis, :perubahan, :status, :sumber)";
            $input_nama_saham = "INSERT INTO nama_saham (kode, nama) VALUES (:kode, :nama)";
        
            // Rata - rata Saham
            $sql_kenaikan = "SELECT id, tanggal FROM kenaikan_nilai WHERE nama_item = :item";
            $sql_ratarata = "SELECT perubahan, status FROM harga_saham WHERE jenis = :jenis";
            $update_ratarata = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
            $input_ratarata = "INSERT INTO kenaikan_nilai (kategori, nama_item, tanggal, perubahan, status) VALUES (:kategori, :item, :tgl, :perubahan, :status)";
        
            // Perkiraan Zakat
            $sql_perkiraan_saham = "SELECT id, tanggal, tgl_zakat FROM perkiraan_zakat WHERE item_id = :id";
            $sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
            $update_perkiraan = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
            $input_perkiraan = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat, item_id) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat, :item_id)";
        
            foreach ($stmt_kepemilikan as $a => $value) {
                $saham['kode'][$a] = $item = $value['nama_item'];
                $userId = $value['user_id'];
                
                // Harga dan nama Saham {
                $stmt_saham = $this->db->prepare($sql_saham);
                $stmt_saham->bindParam(':tgl', $tgl);
                $stmt_saham->bindParam(':jenis', $item);
                if ($stmt_saham->execute() !== false && $stmt_saham->rowCount() == 0) {
                    if($kondisi == 'ok'){
                        $stmt_saham_last = $this->db->prepare($sql_saham_last);
                        $stmt_saham_last->bindParam(':jenis', $item);
                        
                        $url = 'http://mboum.com/api/v1/qu/quote/?symbol='.$item.'&apikey=yMlq1FB0ATdbJnxTB8ieuzGvAsIJwoP5fqqmFAFfBiR2FGkHO7PNeHSzGGtz';
                        $data = file_get_contents($url);
                        $saham_type = json_decode($data, true);
                        $saham_price = $saham_type[0]['regularMarketPrice'];
                        $shortname = $saham_type[0]['shortName'];
                        $sumber = 'http://mboum.com/';
        
                        $harga = round($saham_price * $idr * 100);
                        
                        if ($stmt_saham_last->execute() !== false && $stmt_saham_last->rowCount() > 0) {
                            $result_saham = $stmt_saham_last->fetch(PDO::FETCH_ASSOC);
                            
                            if($harga > $result_saham['harga']){
                                $perubahan = round(($harga - $result_saham['harga']) / $result_saham['harga'],3);
                                $status = '+';
                            }else{
                                $perubahan = round(($result_saham['harga'] - $harga) / $result_saham['harga'],3);
                                $status = '-';
                            }
                        }else{
                            $perubahan = 0;
                            $status = '+';
                            
                            $stmt_nama_saham = $this->db->prepare($input_nama_saham);
                            $stmt_nama_saham->bindParam(':kode', $item);
                            $stmt_nama_saham->bindParam(':nama', $shortname);
                            if ($stmt_nama_saham->execute() == false) {
                                $kondisi = 'Error';
                            }
                        }
                        
                        $stmt_saham = $this->db->prepare($input_saham);
                        $stmt_saham->bindParam(':tanggal', $tgl);
                        $stmt_saham->bindParam(':harga', $harga);
                        $stmt_saham->bindParam(':jenis', $item);
                        $stmt_saham->bindParam(':perubahan', $perubahan);
                        $stmt_saham->bindParam(':status', $status);
                        $stmt_saham->bindParam(':sumber', $sumber);
                        if ($stmt_saham->execute() == false) {
                            $kondisi = 'Error';
                        }
                    }
        
                // Harga dan nama Saham }
        
                }else{
                	$result_saham = $stmt_saham->fetch(PDO::FETCH_ASSOC);
                    $saham['harga'][$item] = $result_saham['harga'];
                    $saham['kuantitas'][$item] = $value['kuantitas'];
                    $saham['all'][$item] = $saham['kuantitas'][$item] * $saham['harga'][$item];
        
        
                	// Rata - rata Saham {
                    $stmt_kenaikan = $this->db->prepare($sql_kenaikan);
                    $stmt_kenaikan->bindParam(':item', $item);
                    if ($stmt_kenaikan->execute() !== false && $stmt_kenaikan->rowCount() > 0) {
                        if($kondisi == 'ok'){
                            $result_kenaikan = $stmt_kenaikan->fetch(PDO::FETCH_ASSOC);
                            if($tgl !== $result_kenaikan['tanggal']){
                                $id = $result_kenaikan['id'];
                                
                                $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                $stmt_ratarata->bindParam(':jenis', $item);
                                if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                    $total_kenaikan = 0;
                                    foreach ($stmt_ratarata as $b => $row) {
                                        if($row['status'] == '+'){
                                            $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                        }else{
                                            $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                        }
                                    }
                                    
                                    $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                    if($total_kenaikan < 0){
                                        $status = '-';
                                    }else{
                                        $status = '+';
                                    }
                                    
                                    $stmt_ratarata = $this->db->prepare($update_ratarata);
                                    $stmt_ratarata->bindParam(':id', $id);
                                    $stmt_ratarata->bindParam(':tgl', $tgl);
                                    $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                    $stmt_ratarata->bindParam(':status', $status);
                                    if ($stmt_ratarata->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }else{
                                $saham['status'][$item] = $result_kenaikan['status'];
                                $saham['ratarata'][$item] = $result_kenaikan['perubahan'];
                                
                                // Perkiraan zakat {
                                $stmt_perkiraan_saham = $this->db->prepare($sql_perkiraan_saham);
                                $stmt_perkiraan_saham->bindParam(':id', $value['id']);
                                if ($stmt_perkiraan_saham->execute() !== false && $stmt_perkiraan_saham->rowCount() > 0) {
                                    $result_perkiraan_saham = $stmt_perkiraan_saham->fetch(PDO::FETCH_ASSOC);
                                    if($result_perkiraan_saham['tanggal'] != $tgl){
                                        if($status_emas !== null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                            $day = 0;
                                            $golds = $harga_emas * 85;
                                            do{
                                                $day++;
                                                
                                                // Saham
                                                $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                $saham['all'][$item] += $kenaikan_saham;
                                                
                                                // Emas
                                                $kenaikan_emas = $golds * $ratarata_emas;
                                                if($status_emas == '+'){
                                                    $golds += $kenaikan_emas;
                                                }else{
                                                    $golds -= $kenaikan_emas;
                                                }
                                                
                                            }while($saham['all'][$item] < $golds);
                                            
                                            $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                            $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                            
                                            if($result_perkiraan_saham['tgl_zakat'] !== $tgl_zakat){
                                                $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                if ($stmt_perkiraan->execute() == false) {
                                                    $kondisi = 'Error';
                                                }else{
        	                                    	$sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
        	                                    	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
        						                    $stmt_nama_saham->bindParam(':kode', $item);
        						                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
        						                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
        	                                    		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
        	                                    		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
        	                                    		$stmt_notif->bindParam(':user_id', $userId);
        				                                $stmt_notif->bindParam(':notifikasi', $message);
        				                                if($stmt_notif->execute() == false) {
        				                                	$kondisi = 'Error';
        				                                }
        				                            }
        	                                    }
                                            }
                                        }else{
                                            $tgl_zakat = $nilai_zakat = null;
                                            
                                            $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                            $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                            $stmt_perkiraan->bindParam(':tgl', $tgl);
                                            $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                            $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                            if ($stmt_perkiraan->execute() == false) {
                                                $kondisi = 'Error';
                                            } 
                                        }
                                    }
                                }else{
                                    if($status_emas != null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                        $day = 0;
                                        $golds = $harga_emas * 85;
                                        do{
                                            $day++;
                                            
                                            // Saham
                                            $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                            $saham['all'][$item] += $kenaikan_saham;
                                            
                                            // Emas
                                            $kenaikan_emas = $golds * $ratarata_emas;
                                            if($status_emas == '+'){
                                                $golds += $kenaikan_emas;
                                            }else{
                                                $golds -= $kenaikan_emas;
                                            }
                                            
                                        }while($saham['all'][$item] < $golds);
                                        
                                        $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                        $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                        
                                        $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                        $stmt_perkiraan->bindParam(':userId', $userId);
                                        $stmt_perkiraan->bindParam(':kategori', $kategori);
                                        $stmt_perkiraan->bindParam(':tgl', $tgl);
                                        $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                        $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                        if ($stmt_perkiraan->execute() == false) {
                                            $kondisi = 'Error';
                                        }else{
                                        	$sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
                                        	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
    					                    $stmt_nama_saham->bindParam(':kode', $item);
    					                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
    					                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
                                        		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
                                        		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                        		$stmt_notif->bindParam(':user_id', $userId);
    			                                $stmt_notif->bindParam(':notifikasi', $message);
    			                                if($stmt_notif->execute() == false) {
    			                                	$kondisi = 'Error';
    			                                }
    			                            }
                                        }
                                    }else{
                                        $tgl_zakat = $nilai_zakat = null;
                                        
                                        $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                        $stmt_perkiraan->bindParam(':userId', $userId);
                                        $stmt_perkiraan->bindParam(':kategori', $kategori);
                                        $stmt_perkiraan->bindParam(':tgl', $tgl);
                                        $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                        $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                        $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                        if ($stmt_perkiraan->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }
                                // Perkiraan zakat }
                            }
                        }
                    }else{
                       if($kondisi == 'ok'){
                            $stmt_ratarata = $this->db->prepare($sql_ratarata);
                            $stmt_ratarata->bindParam(':jenis', $item);
                            if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                $total_kenaikan = 0;
                                foreach ($stmt_ratarata as $b => $row) {
                                    if($row['status'] == '+'){
                                        $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                    }else{
                                        $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                    }
                                }
                                
                                $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                if($total_kenaikan < 0){
                                    $status = '-';
                                }else{
                                    $status = '+';
                                }
                                
                                $stmt_ratarata = $this->db->prepare($input_ratarata);
                                $stmt_ratarata->bindParam(':kategori', $kategori);
                                $stmt_ratarata->bindParam(':item', $item);
                                $stmt_ratarata->bindParam(':tgl', $tgl);
                                $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                $stmt_ratarata->bindParam(':status', $status);
                                if ($stmt_ratarata->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        } 
                    }
                    // Rata - rata Saham }
        
                }
            }
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

        $update = $element->children(0)->children(3)->children(0)->plaintext;

        $strlen = strlen($update);
        $update = substr($update, 0, $strlen - 2);

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
    
    function rupiah($angka){
		$hasil_rupiah = number_format($angka,2,',','.');
		return $hasil_rupiah;
	}

    $app->post('/pencatatan/saham', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $userId = $body->user_id;
        $kategori = "Saham";
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktuKepemilikan = $body->waktu_kepemilikan;
        $waktu = $body->waktu;
        $tanggal = $body->tanggal;
        $tgl = date("d-m-Y");
        $kondisi = 'ok';

        try {
            $sql_saham = "SELECT * FROM harga_saham WHERE tanggal = :tgl AND jenis = :jenis";
            $stmt_saham = $this->db->prepare($sql_saham);
            $stmt_saham->bindParam(':tgl', $tgl);
            $stmt_saham->bindParam(':jenis', $namaItem);
            if ($stmt_saham->execute() !== false && $stmt_saham->rowCount() == 0) {
                $url2 = 'http://www.floatrates.com/daily/usd.json';
                $data2 = file_get_contents($url2);
                $usdtoidr = json_decode($data2, true);
                $idr = $usdtoidr['idr']['rate'];
                
                $stmt_saham_last = $this->db->prepare("SELECT harga FROM harga_saham WHERE jenis = :jenis ORDER BY id DESC LIMIT 1");
                $stmt_saham_last->bindParam(':jenis', $namaItem);
                    
                $url = 'http://mboum.com/api/v1/qu/quote/?symbol='.$namaItem.'&apikey=yMlq1FB0ATdbJnxTB8ieuzGvAsIJwoP5fqqmFAFfBiR2FGkHO7PNeHSzGGtz';
                $data = file_get_contents($url);
                $saham_type = json_decode($data, true);
                $saham_price = $saham_type[0]['regularMarketPrice'];
                $sumber = 'http://mboum.com/';

                $harga = round($saham_price * $idr * 100);
                    
                if ($stmt_saham_last->execute() !== false && $stmt_saham_last->rowCount() > 0) {
                    $result_saham = $stmt_saham_last->fetch(PDO::FETCH_ASSOC);
                    
                    if($harga > $result_saham['harga']){
                        $perubahan = round(($harga - $result_saham['harga']) / $result_saham['harga'],3);
                        $status = '+';
                    }else{
                        $perubahan = round(($result_saham['harga'] - $harga) / $result_saham['harga'],3);
                        $status = '-';
                    }
                }else{
                    $perubahan = 0;
                    $status = '+';
                }
                    
                $input_sql = "INSERT INTO harga_saham (tanggal, harga, jenis, perubahan, status, sumber) VALUES (:tanggal, :harga, :jenis, :perubahan, :status, :sumber)";
                $stmt4 = $this->db->prepare($input_sql);
                $stmt4->bindParam(':tanggal', $tgl);
                $stmt4->bindParam(':harga', $harga);
                $stmt4->bindParam(':jenis', $namaItem);
                $stmt4->bindParam(':perubahan', $perubahan);
                $stmt4->bindParam(':status', $status);
                $stmt4->bindParam(':sumber', $sumber);
                if ($stmt4->execute() == false) {
                    $kondisi = 'error';
                }
            } 
            
            if($kondisi == 'ok'){
                $sql = "INSERT INTO kekayaan (user_id, kategori, nama_item, keterangan, kuantitas, waktu_kepemilikan, waktu, tanggal) VALUES (:user_id, :kategori, :nama_item, :keterangan, :kuantitas, :waktu_kepemilikan, :waktu, :tanggal)";
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
                }else{
                    return $response->withJson(array(
                        "status" => "failed",
                        "message" => "Penambahan item gagal!"
                    ), 200);
                }
            }else {
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
    
    $app->post('/pencatatan/properti', function ($request, $response, $args) {
        $body = json_decode($request->getBody());

        $userId = $body->user_id;
        $kategori = "Properti";
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktuKepemilikan = $body->waktu_kepemilikan;
        $waktu = $body->waktu;
        $tanggal = $body->tanggal;
        $tgl = date("d-m-Y");
        $kondisi = 'ok';

        try {
            $sql_emas = "SELECT * FROM harga_emas WHERE tanggal = :tgl";
            $stmt_emas = $this->db->prepare($sql_emas);
            $stmt_emas->bindParam(':tgl', $tgl);
            if ($stmt_emas->execute() !== false && $stmt_emas->rowCount() == 0) {
                $url = "https://www.indogold.id/harga-emas-hari-ini";
                require_once("../util/simple_html_dom.php");
                $html = new simple_html_dom();
                $html->load_file($url);
    
                $element = $html->find('.Rectangle-price')[0];
                $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                $harga_emas = preg_replace('/\D/', '', $harga_emas);
                
                $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
                $stmt_emas_last = $this->db->prepare($sql_emas_last);
                if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                    if($harga_emas > $result_emas['harga']){
                        $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                        $status = '+';
                    }else{
                        $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                        $status = '-';
                    }
                    
                    $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
                    $stmt_input_emas = $this->db->prepare($input_emas);
                    $stmt_input_emas->bindParam(':tanggal', $tgl);
                    $stmt_input_emas->bindParam(':harga', $harga_emas);
                    $stmt_input_emas->bindParam(':perubahan', $perubahan);
                    $stmt_input_emas->bindParam(':status', $status);
                    $stmt_input_emas->bindParam(':sumber', $url);
        
                    if ($stmt_input_emas->execute() == false) {
                        $kondisi = "Error";
                    }
                }
            }else{
                $result_emas = $stmt_emas->fetchAll(PDO::FETCH_ASSOC);
                
                $harga_emas = $result_emas['harga'];
            }
            
            if($kondisi == 'ok'){
                $sql_kekayaan = "INSERT INTO kekayaan (user_id, kategori, nama_item, keterangan, kuantitas, waktu_kepemilikan, waktu, tanggal) VALUES (:user_id, :kategori, :nama_item, :keterangan, :kuantitas, :waktu_kepemilikan, :waktu, :tanggal)";
                $stmt_kekayaan = $this->db->prepare($sql_kekayaan);
                $stmt_kekayaan->bindParam(':user_id', $userId);
                $stmt_kekayaan->bindParam(':kategori', $kategori);
                $stmt_kekayaan->bindParam(':nama_item', $namaItem);
                $stmt_kekayaan->bindParam(':keterangan', $keterangan);
                $stmt_kekayaan->bindParam(':kuantitas', $kuantitas);
                $stmt_kekayaan->bindParam(':waktu_kepemilikan', $waktuKepemilikan);
                $stmt_kekayaan->bindParam(':waktu', $waktu);
                $stmt_kekayaan->bindParam(':tanggal', $tanggal);
                if ($stmt_kekayaan->execute() !== false) {
                    
                    $tgl_kepemilikan = str_replace("/","-",$waktuKepemilikan);
                    $categor = "Properti";
                    $sql_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat, item_id) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat, :item)";
                    $id = $this->db->lastInsertId();
                    
                    if($kuantitas > $harga_emas * 85){
                        $tgl_zakat = date("d-m-Y",strtotime("+12 month",strtotime($tgl_kepemilikan)));
                        $nilai_zakat = $kuantitas * 0.025;
                        
                        $stmt_zakat = $this->db->prepare($sql_zakat);
                        $stmt_zakat->bindParam(':userId', $userId);
                        $stmt_zakat->bindParam(':kategori', $categor);
                        $stmt_zakat->bindParam(':tgl', $tgl);
                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                        $stmt_zakat->bindParam(':item', $id);
                        if ($stmt_zakat->execute() !== false){
                            return $response->withJson(array(
                                "status" => "success",
                                "message" => "Penambahan item berhasil!"
                            ), 200);
                        }else{
                            return $response->withJson(array(
                                "status" => "failed",
                                "message" => "Penambahan item gagal!"
                            ), 200);
                        }
                    }else{
                        $tgl_zakat = null;
                        $nilai_zakat = null;
                        
                        $stmt_zakat = $this->db->prepare($sql_zakat);
                        $stmt_zakat->bindParam(':userId', $userId);
                        $stmt_zakat->bindParam(':kategori', $categor);
                        $stmt_zakat->bindParam(':tgl', $tgl);
                        $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                        $stmt_zakat->bindParam(':item', $id);
                        if ($stmt_zakat->execute() !== false){
                            return $response->withJson(array(
                                "status" => "success",
                                "message" => "Penambahan item berhasil!"
                            ), 200);
                        }else{
                            return $response->withJson(array(
                                "status" => "failed",
                                "message" => "Penambahan item gagal!"
                            ), 200);
                        }
                    }
                }else{
                    return $response->withJson(array(
                        "status" => "failed",
                        "message" => "Penambahan item gagal!"
                    ), 200);
                }
            }else {
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
    
    $app->get('/pencatatan/properti', function ($request, $response, $args) {
        $userId = $request->getQueryParams()['user_id'];
        try {
            $kategori = "Properti";
            
            $sql_kepemilikan = "SELECT * FROM kekayaan WHERE kategori = :kategori AND user_id = :user_id ORDER BY id DESC";
            $stmt_kepemilikan = $this->db->prepare($sql_kepemilikan);
            $stmt_kepemilikan->bindParam(':kategori', $kategori);
            $stmt_kepemilikan->bindParam(':user_id', $userId);
            if($stmt_kepemilikan->execute() !== false && $stmt_kepemilikan->rowCount() > 0){
                $result = $stmt_kepemilikan->fetchAll(PDO::FETCH_ASSOC);
                return $response->withJson($result, 200);
            }
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });
    
    $app->get('/pencatatan/saham', function ($request, $response, $args) {
        $userId = $request->getQueryParams()['user_id'];
        try {
            
            $tgl = date("d-m-Y");
            $ktgr = "Emas";
            $kondisi = 'ok';
            $status_emas = null;
            $sql_emas = "SELECT id, harga FROM harga_emas WHERE tanggal = :tgl";
            $stmt_emas = $this->db->prepare($sql_emas);
            $stmt_emas->bindParam(':tgl', $tgl);
            if ($stmt_emas->execute() !== false) {
                $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
                $sql_kenaikan_gold = "SELECT id, tanggal, perubahan, status FROM kenaikan_nilai WHERE kategori = :kategori";
                $sql_ratarata_emas = "SELECT perubahan, status FROM harga_emas";
                $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
                $update_ratarata_emas = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
                $input_ratarata_emas = "INSERT INTO kenaikan_nilai (kategori, tanggal, perubahan, status) VALUES (:kategori, :tgl, :perubahan, :status)";
                if($stmt_emas->rowCount() == 0){
                    $stmt_emas_last = $this->db->prepare($sql_emas_last);
                    if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                        $result_emas = $stmt_emas_last->fetch(PDO::FETCH_ASSOC);
                        
                        $url = "https://www.indogold.id/harga-emas-hari-ini";
                        
                        require_once("../util/simple_html_dom.php");
            
                        $html = new simple_html_dom();
                        $html->load_file($url);
            
                        $element = $html->find('.Rectangle-price')[0];
                        $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                        $harga_emas = preg_replace('/\D/', '', $harga_emas);
                        
                        if($harga_emas > $result_emas['harga']){
                            $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                            $status = '+';
                        }else{
                            $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                            $status = '-';
                        }
                        
                        $stmt_input_emas = $this->db->prepare($input_emas);
                        $stmt_input_emas->bindParam(':tanggal', $tgl);
                        $stmt_input_emas->bindParam(':harga', $harga_emas);
                        $stmt_input_emas->bindParam(':perubahan', $perubahan);
                        $stmt_input_emas->bindParam(':status', $status);
                        $stmt_input_emas->bindParam(':sumber', $url);
            
                        if ($stmt_input_emas->execute() == false) {
                            $kondisi = "Error";
                        }
                    }
                }else{
                    $result_harga_emas = $stmt_emas->fetch(PDO::FETCH_ASSOC);
                    $harga_emas = $result_harga_emas['harga'];
                    
                    
                    $stmt_kenaikan_gold = $this->db->prepare($sql_kenaikan_gold);
                    $stmt_kenaikan_gold->bindParam(':kategori', $ktgr);
                    if ($stmt_kenaikan_gold->execute() !== false) {
                        if($stmt_kenaikan_gold->rowCount() > 0){
                            $result_gold = $stmt_kenaikan_gold->fetch(PDO::FETCH_ASSOC);
                            
                            $ratarata_emas = $result_gold['perubahan'];
                            $status_emas = $result_gold['status'];
                            
                            if($result_gold['tanggal'] != $tgl){
                                $id = $result_gold['id'];
                                
                                $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                                if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                    $total_kenaikan = 0;
                                    foreach ($stmt_ratarata_emas as $a => $row) {
                                        if($row['status'] == '+'){
                                            $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                        }else{
                                            $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                        }
                                    }
                                    
                                    $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                    if($total_kenaikan < 0){
                                        $status_emas = $status = '-';
                                    }else{
                                        $status_emas = $status = '+';
                                    }
                                    
                                    $stmt_update_ratarata_emas = $this->db->prepare($update_ratarata_emas);
                                    $stmt_update_ratarata_emas->bindParam(':id', $id);
                                    $stmt_update_ratarata_emas->bindParam(':tgl', $tgl);
                                    $stmt_update_ratarata_emas->bindParam(':perubahan', $ratarata);
                                    $stmt_update_ratarata_emas->bindParam(':status', $status);
                                    if ($stmt_update_ratarata_emas->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }else{
                            $stmt_ratarata_emas = $this->db->prepare($sql_ratarata_emas);
                            if ($stmt_ratarata_emas->execute() !== false && $stmt_ratarata_emas->rowCount() > 0) {
                                $total_kenaikan = 0;
                                foreach ($stmt_ratarata_emas as $a => $row) {
                                    if($row['status'] == '+'){
                                        $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                    }else{
                                        $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                    }
                                }
                                
                                $ratarata_emas = $ratarata = round(abs($total_kenaikan / ($a + 1)),3);
                                if($total_kenaikan < 0){
                                    $status_emas = $status = '-';
                                }else{
                                    $status_emas = $status = '+';
                                }
                                
                                $stmt_input_ratarata_emas = $this->db->prepare($input_ratarata_emas);
                                $stmt_input_ratarata_emas->bindParam(':kategori', $ktgr);
                                $stmt_input_ratarata_emas->bindParam(':tgl', $tgl);
                                $stmt_input_ratarata_emas->bindParam(':perubahan', $ratarata);
                                $stmt_input_ratarata_emas->bindParam(':status', $status);
                                if ($stmt_input_ratarata_emas->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }
                    }
                }
            }
             
            // Emas
            $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
            $stmt_cek = $this->db->prepare($sql_cek);
            $stmt_cek->bindParam(':userId', $userId);
            $stmt_cek->bindParam(':kategori', $ktgr);
            if ($stmt_cek->execute() !== false) {
                if($stmt_cek->rowCount() > 0){
                    $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                    $id = $result_cek['id'];
                    $tgl_cek_zakat = $result_cek['tgl_zakat'];
                    $tgl_cek = $result_cek['tanggal'];
                }else{
                    $tgl_cek_zakat = 'belum ada';
                    $tgl_cek = null;
                }
                
                if($tgl_cek != $tgl){
                    $sql_kepemilikan_emas = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                    $stmt_kepemilikan_emas = $this->db->prepare($sql_kepemilikan_emas);
                    $stmt_kepemilikan_emas->bindParam(':userId', $userId);
                    $stmt_kepemilikan_emas->bindParam(':kategori', $ktgr);
                    if ($stmt_kepemilikan_emas->execute() !== false && $stmt_kepemilikan_emas->rowCount() > 1) {
                        $urutan = array();
                        $total_kepemilikan = 0;
                        $tgl_terakhir = '';
                        foreach ($stmt_kepemilikan_emas as $a => $val) {
                            $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                            $total_kepemilikan += $val['kuantitas'];
                            $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                        }
                        sort($urutan);
                        
                        if($total_kepemilikan < 85){
                            $golds = $total_kepemilikan / ( $a - 1 );
                            
                            $diff = 0;
                            for($i = 0; $i < $a; $i++){
                                $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                $diff += $dif;
                            }
                            
                            $day = round($diff / $a);
                            $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                            if($cek_tgl > strtotime('now')){
                                $days = 0;
                                if($total_kepemilikan < 85){
                                    do{
                                        $days += $day;
                                        $total_kepemilikan += $golds;
                                    }while($total_kepemilikan < 85);
                                    
                                    $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                    
                                    $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                                    
                                    if($tgl_cek_zakat == 'belum ada'){
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $ktgr);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                    
                                    if($kondisi !== 'Error'){
                                        $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                		$stmt_notif->bindParam(':user_id', $userId);
    	                                $stmt_notif->bindParam(':notifikasi', $message);
    	                                if($stmt_notif->execute() == false) {
    	                                    $kondisi = 'Error';
    	                                }
                                    }
                                }
                            }else{
                                if($tgl_cek_zakat != 'belum ada'){
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $ktgr);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }else{
                            rsort($urutan);
                            $now = strtotime('now');
                            
                            $tgl_zakat = strtotime("+12 month",$urutan[0]);
                            if($tgl_zakat < $now){
                                $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                            }
                            
                            $nilai_zakat = $total_kepemilikan * $harga_emas * 0.025;
                            
                            if($tgl_cek_zakat == 'belum ada'){
                                $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                $stmt_zakat = $this->db->prepare($input_zakat);
                                $stmt_zakat->bindParam(':userId', $userId);
                                $stmt_zakat->bindParam(':kategori', $ktgr);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                if ($stmt_zakat->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }else{
                                $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                $stmt_zakat = $this->db->prepare($update_zakat);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                $stmt_zakat->bindParam(':id', $id);
                                if ($stmt_zakat->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                            
                            if($kondisi !== 'Error'){
                                $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                        		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                        		$stmt_notif->bindParam(':user_id', $userId);
                                $stmt_notif->bindParam(':notifikasi', $message);
                                if($stmt_notif->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }
                    }
                }
            }
            
            // Perak
            $url = "https://www.indogold.id/harga-emas-hari-ini";
            require_once("../util/simple_html_dom.php");
            $html = new simple_html_dom();
            $html->load_file($url);
            $element = $html->find('.Rectangle-price')[0];
            $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
            $harga_perak = preg_replace('/\D/', '', $harga_perak);
            $category = 'Perak';
            
            $sql_cek = "SELECT * FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
            $stmt_cek = $this->db->prepare($sql_cek);
            $stmt_cek->bindParam(':userId', $userId);
            $stmt_cek->bindParam(':kategori', $category);
            if ($stmt_cek->execute() !== false) {
                if($stmt_cek->rowCount() > 0){
                    $result_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);
                    $id = $result_cek['id'];
                    $tgl_cek_zakat = $result_cek['tgl_zakat'];
                    $tgl_cek = $result_cek['tanggal'];
                }else{
                    $tgl_cek_zakat = 'belum ada';
                    $tgl_cek = null;
                }
                
                if($tgl_cek != $tgl){
                    $sql_kepemilikan_perak = "SELECT * FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                    $stmt_kepemilikan_perak = $this->db->prepare($sql_kepemilikan_perak);
                    $stmt_kepemilikan_perak->bindParam(':userId', $userId);
                    $stmt_kepemilikan_perak->bindParam(':kategori', $category);
                    if ($stmt_kepemilikan_perak->execute() !== false && $stmt_kepemilikan_perak->rowCount() > 1) {
                        $urutan = array();
                        $total_kepemilikan = 0;
                        $tgl_terakhir = '';
                        foreach ($stmt_kepemilikan_perak as $a => $val) {
                            $urutan[$a] = strtotime(str_replace('/','-',$val['waktu_kepemilikan']));
                            $total_kepemilikan += $val['kuantitas'];
                            $tgl_terakhir = date("d-m-Y",$urutan[$a]);
                        }
                        sort($urutan);
                        
                        if($total_kepemilikan < 595){
                            $silvers = $total_kepemilikan / ( $a - 1 );
                            
                            $diff = 0;
                            for($i = 0; $i < $a; $i++){
                                $dif = floor(($urutan[$i + 1] - $urutan[$i]) / (60*60*24));
                                $diff += $dif;
                            }
                            
                            $day = round($diff / $a);
                            $cek_tgl = strtotime("+".$day." days",strtotime($tgl_terakhir));
                            if($cek_tgl > strtotime('now')){
                                $days = 0;
                                if($total_kepemilikan < 595){
                                    do{
                                        $days += $day;
                                        $total_kepemilikan += $silvers;
                                    }while($total_kepemilikan < 595);
                                    
                                    $tgl_zakat = strtotime("+".$days." days",strtotime($tgl_terakhir));
                                    $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                                    
                                    $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                                    
                                    if($tgl_cek_zakat == 'belum ada'){
                                        $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                        $stmt_zakat = $this->db->prepare($input_zakat);
                                        $stmt_zakat->bindParam(':userId', $userId);
                                        $stmt_zakat->bindParam(':kategori', $category);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }else{
                                        $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                        $stmt_zakat = $this->db->prepare($update_zakat);
                                        $stmt_zakat->bindParam(':tgl', $tgl);
                                        $stmt_zakat->bindParam(':tgl_zakat', date("d-m-Y",$tgl_zakat));
                                        $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                        $stmt_zakat->bindParam(':id', $id);
                                        if ($stmt_zakat->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                    
                                    if($kondisi !== 'Error'){
                                        $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                                		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                		$stmt_notif->bindParam(':user_id', $userId);
    	                                $stmt_notif->bindParam(':notifikasi', $message);
    	                                if($stmt_notif->execute() == false) {
    	                                    $kondisi = 'Error';
    	                                }
                                    }
                                }
                            }else{
                                if($tgl_cek_zakat != 'belum ada'){
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                    $stmt_zakat = $this->db->prepare($update_zakat);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    $stmt_zakat->bindParam(':id', $id);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }else{
                                    $tgl_zakat = null;
                                    $nilai_zakat = null;
                                    
                                    $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                    $stmt_zakat = $this->db->prepare($input_zakat);
                                    $stmt_zakat->bindParam(':userId', $userId);
                                    $stmt_zakat->bindParam(':kategori', $category);
                                    $stmt_zakat->bindParam(':tgl', $tgl);
                                    $stmt_zakat->bindParam(':tgl_zakat', $tgl_zakat);
                                    $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                    if ($stmt_zakat->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            }
                        }else{
                            rsort($urutan);
                            $now = strtotime('now');
                            
                            $tgl_zakat = strtotime("+12 month",$urutan[0]);
                            if($tgl_zakat < $now){
                                $tgl_zakat = strtotime("+12 month",$tgl_zakat);
                            }
                            
                            $nilai_zakat = $total_kepemilikan * $harga_perak * 0.025;
                            
                            if($tgl_cek_zakat == 'belum ada'){
                                $input_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat)";
                                $stmt_zakat = $this->db->prepare($input_zakat);
                                $stmt_zakat->bindParam(':userId', $userId);
                                $stmt_zakat->bindParam(':kategori', $category);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                if ($stmt_zakat->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }else{
                                $update_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                                $stmt_zakat = $this->db->prepare($update_zakat);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y',$tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                $stmt_zakat->bindParam(':id', $id);
                                if ($stmt_zakat->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                            
                            if($kondisi !== 'Error'){
                                $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".date('d-m-Y',$tgl_zakat)." senilai ".rupiah($nilai_zakat);
                        		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                        		$stmt_notif->bindParam(':user_id', $userId);
                                $stmt_notif->bindParam(':notifikasi', $message);
                                if($stmt_notif->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                        }
                    }
                }
            }
            
            // Saham
            $kategori = "Saham";
            $batas = ($harga_emas * 170) / 3;
            $saham = array();
            $sql_kepemilikan = "SELECT * FROM kekayaan WHERE kategori = :kategori AND user_id = :user_id ORDER BY id DESC";
            $stmt_kepemilikan = $this->db->prepare($sql_kepemilikan);
            $stmt_kepemilikan->bindParam(':kategori', $kategori);
            $stmt_kepemilikan->bindParam(':user_id', $userId);
            if($stmt_kepemilikan->execute() !== false && $stmt_kepemilikan->rowCount() > 0){
                $result = $stmt_kepemilikan->fetchAll(PDO::FETCH_ASSOC);
                
                $url2 = 'http://www.floatrates.com/daily/usd.json';
                $data2 = file_get_contents($url2);
                $usdtoidr = json_decode($data2, true);
                $idr = $usdtoidr['idr']['rate'];
                
                // Harga dan nama Saham
                $sql_saham = "SELECT * FROM harga_saham WHERE tanggal = :tgl AND jenis = :jenis";
                $sql_saham_last = "SELECT harga FROM harga_saham WHERE jenis = :jenis ORDER BY id DESC LIMIT 1";
                $input_saham = "INSERT INTO harga_saham (tanggal, harga, jenis, perubahan, status, sumber) VALUES (:tanggal, :harga, :jenis, :perubahan, :status, :sumber)";
                $input_nama_saham = "INSERT INTO nama_saham (kode, nama) VALUES (:kode, :nama)";
            
                // Rata - rata Saham
                $sql_kenaikan = "SELECT id, tanggal FROM kenaikan_nilai WHERE nama_item = :item";
                $sql_ratarata = "SELECT perubahan, status FROM harga_saham WHERE jenis = :jenis";
                $update_ratarata = "UPDATE kenaikan_nilai SET tanggal = :tgl, perubahan = :perubahan, status = :status WHERE id = :id";
                $input_ratarata = "INSERT INTO kenaikan_nilai (kategori, nama_item, tanggal, perubahan, status) VALUES (:kategori, :item, :tgl, :perubahan, :status)";
            
                // Perkiraan Zakat
                $sql_perkiraan_saham = "SELECT id, tanggal, tgl_zakat FROM perkiraan_zakat WHERE item_id = :id";
                $sql_nama_saham = "SELECT nama FROM nama_saham WHERE kode = :kode";
                $update_perkiraan = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :id";
                $input_perkiraan = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat, item_id) VALUES (:userId, :kategori, :tgl, :tgl_zakat, :zakat, :item_id)";
                foreach ($result as $a => $value) {
                	$saham['kode'][$a] = $item = $value['nama_item'];
                    
                	$stmt_saham = $this->db->prepare($sql_saham);
                    $stmt_saham->bindParam(':tgl', $tgl);
                    $stmt_saham->bindParam(':jenis', $item);
                    if ($stmt_saham->execute() !== false && $stmt_saham->rowCount() == 0) {
                        if($kondisi == 'ok'){
                            $stmt_saham_last = $this->db->prepare($sql_saham_last);
                            $stmt_saham_last->bindParam(':jenis', $item);
                            
                            $url = 'http://mboum.com/api/v1/qu/quote/?symbol='.$item.'&apikey=yMlq1FB0ATdbJnxTB8ieuzGvAsIJwoP5fqqmFAFfBiR2FGkHO7PNeHSzGGtz';
                            $data = file_get_contents($url);
                            $saham_type = json_decode($data, true);
                            $saham_price = $saham_type[0]['regularMarketPrice'];
                            $shortname = $saham_type[0]['shortName'];
                            $sumber = 'http://mboum.com/';
            
                            $saham['harga'][$item] = $harga = round($saham_price * $idr * 100);
                            
                            if ($stmt_saham_last->execute() !== false && $stmt_saham_last->rowCount() > 0) {
                                $result_saham = $stmt_saham_last->fetch(PDO::FETCH_ASSOC);
                                
                                if($harga > $result_saham['harga']){
                                    $perubahan = round(($harga - $result_saham['harga']) / $result_saham['harga'],3);
                                    $status = '+';
                                }else{
                                    $perubahan = round(($result_saham['harga'] - $harga) / $result_saham['harga'],3);
                                    $status = '-';
                                }
                            }else{
                                $perubahan = 0;
                                $status = '+';
                                
                                $stmt_nama_saham = $this->db->prepare($input_nama_saham);
                                $stmt_nama_saham->bindParam(':kode', $item);
                                $stmt_nama_saham->bindParam(':nama', $shortname);
                                if ($stmt_nama_saham->execute() == false) {
                                    $kondisi = 'Error';
                                }
                            }
                            
                            $stmt_saham = $this->db->prepare($input_saham);
                            $stmt_saham->bindParam(':tanggal', $tgl);
                            $stmt_saham->bindParam(':harga', $harga);
                            $stmt_saham->bindParam(':jenis', $item);
                            $stmt_saham->bindParam(':perubahan', $perubahan);
                            $stmt_saham->bindParam(':status', $status);
                            $stmt_saham->bindParam(':sumber', $sumber);
                            if ($stmt_saham->execute() == false) {
                                $kondisi = 'Error';
                            }
                        }
                    }else{
                        $result_saham = $stmt_saham->fetch(PDO::FETCH_ASSOC);
                        $saham['harga'][$item] = $result_saham['harga'];
                        $saham['kuantitas'][$item] = $value['kuantitas'];
                        $saham['all'][$item] = $saham['kuantitas'][$item] * $saham['harga'][$item];
                        
                        $stmt_kenaikan = $this->db->prepare($sql_kenaikan);
                        $stmt_kenaikan->bindParam(':item', $item);
                        if ($stmt_kenaikan->execute() !== false && $stmt_kenaikan->rowCount() > 0) {
                            if($kondisi == 'ok'){
                                $result_kenaikan = $stmt_kenaikan->fetch(PDO::FETCH_ASSOC);
                                if($tgl !== $result_kenaikan['tanggal']){
                                    $id = $result_kenaikan['id'];
                                    
                                    $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                    $stmt_ratarata->bindParam(':jenis', $item);
                                    if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                        $total_kenaikan = 0;
                                        foreach ($stmt_ratarata as $b => $row) {
                                            if($row['status'] == '+'){
                                                $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                            }else{
                                                $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                            }
                                        }
                                        
                                        $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                        if($total_kenaikan < 0){
                                            $status = '-';
                                        }else{
                                            $status = '+';
                                        }
                                        
                                        $stmt_ratarata = $this->db->prepare($update_ratarata);
                                        $stmt_ratarata->bindParam(':id', $id);
                                        $stmt_ratarata->bindParam(':tgl', $tgl);
                                        $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                        $stmt_ratarata->bindParam(':status', $status);
                                        if ($stmt_ratarata->execute() == false) {
                                            $kondisi = 'Error';
                                        }
                                    }
                                }else{
                                    $saham['status'][$item] = $result_kenaikan['status'];
                                    $saham['ratarata'][$item] = $result_kenaikan['perubahan'];
                                    
                                    $stmt_perkiraan_saham = $this->db->prepare($sql_perkiraan_saham);
                                    $stmt_perkiraan_saham->bindParam(':id', $value['id']);
                                    if ($stmt_perkiraan_saham->execute() !== false && $stmt_perkiraan_saham->rowCount() > 0) {
                                        $result_perkiraan_saham = $stmt_perkiraan_saham->fetch(PDO::FETCH_ASSOC);
                                        if($result_perkiraan_saham['tanggal'] != $tgl){
                                            if($status_emas !== null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                                $day = 0;
                                                $golds = $harga_emas * 85;
                                                do{
                                                    $day++;
                                                    
                                                    // Saham
                                                    $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                    $saham['all'][$item] += $kenaikan_saham;
                                                    
                                                    // Emas
                                                    $kenaikan_emas = $golds * $ratarata_emas;
                                                    if($status_emas == '+'){
                                                        $golds += $kenaikan_emas;
                                                    }else{
                                                        $golds -= $kenaikan_emas;
                                                    }
                                                    
                                                }while($saham['all'][$item] < $golds);
                                                
                                                $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                                $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                                
                                                if($result_perkiraan_saham['tgl_zakat'] !== $tgl_zakat){
                                                    $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                    $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                    $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                    $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                    $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                    if ($stmt_perkiraan->execute() == false) {
                                                        $kondisi = 'Error';
                                                    }
                                                }
                                            }else{
                                                $tgl_zakat = $nilai_zakat = null;
                                                
                                                $stmt_perkiraan = $this->db->prepare($update_perkiraan);
                                                $stmt_perkiraan->bindParam(':id', $result_perkiraan_saham['id']);
                                                $stmt_perkiraan->bindParam(':tgl', $tgl);
                                                $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                                $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                                if ($stmt_perkiraan->execute() == false) {
                                                    $kondisi = 'Error';
                                                } 
                                            }
                                        }
                                    }else{
                                        if($status_emas != null && $batas < $saham['all'][$item] && $saham['status'][$item] !== '-' && (($status_emas == '+' && $saham['ratarata'][$item] > $ratarata_emas) || $status_emas == '-')){
                                            $day = 0;
                                            $golds = $harga_emas * 85;
                                            do{
                                                $day++;
                                                
                                                // Saham
                                                $kenaikan_saham = $saham['all'][$item] * $saham['ratarata'][$item];
                                                $saham['all'][$item] += $kenaikan_saham;
                                                
                                                // Emas
                                                $kenaikan_emas = $golds * $ratarata_emas;
                                                if($status_emas == '+'){
                                                    $golds += $kenaikan_emas;
                                                }else{
                                                    $golds -= $kenaikan_emas;
                                                }
                                                
                                            }while($saham['all'][$item] < $golds);
                                            
                                            $nilai_zakat = round($saham['all'][$item] * 0.025,3);
                                            $tgl_zakat = date("d-m-Y",strtotime("+".$day." days",strtotime('now')));
                                            
                                            $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                            $stmt_perkiraan->bindParam(':userId', $userId);
                                            $stmt_perkiraan->bindParam(':kategori', $kategori);
                                            $stmt_perkiraan->bindParam(':tgl', $tgl);
                                            $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                            $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                            $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                            if ($stmt_perkiraan->execute() == false) {
                                                $kondisi = 'Error';
                                            }else{
                                            	$stmt_nama_saham = $this->db->prepare($sql_nama_saham);
							                    $stmt_nama_saham->bindParam(':kode', $item);
							                    if ($stmt_nama_saham->execute() !== false && $stmt_nama_saham->rowCount() > 0) {
							                    	$result_nama_saham = $stmt_nama_saham->fetch(PDO::FETCH_ASSOC);
                                            		$message = "Pembayaran zakat untuk tabungan saham kamu di ".$result_nama_saham['nama']." diperkirakan pada tanggal ".$tgl_zakat." membayar zakat senilai ".rupiah($nilai_zakat);
                                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                                            		$stmt_notif->bindParam(':user_id', $userId);
					                                $stmt_notif->bindParam(':notifikasi', $message);
					                                if($stmt_notif->execute() == false) {
					                                	$kondisi = 'Error';
					                                }
							                    }
                                            }
                                        }else{
                                            $tgl_zakat = $nilai_zakat = null;
                                            
                                            $stmt_perkiraan = $this->db->prepare($input_perkiraan);
                                            $stmt_perkiraan->bindParam(':userId', $userId);
                                            $stmt_perkiraan->bindParam(':kategori', $kategori);
                                            $stmt_perkiraan->bindParam(':tgl', $tgl);
                                            $stmt_perkiraan->bindParam(':tgl_zakat', $tgl_zakat);
                                            $stmt_perkiraan->bindParam(':zakat', $nilai_zakat);
                                            $stmt_perkiraan->bindParam(':item_id', $value['id']);
                                            if ($stmt_perkiraan->execute() == false) {
                                                $kondisi = 'Error';
                                            }
                                        }
                                    }
                                }
                            }
                        }else{
                           if($kondisi == 'ok'){
                                $stmt_ratarata = $this->db->prepare($sql_ratarata);
                                $stmt_ratarata->bindParam(':jenis', $item);
                                if ($stmt_ratarata->execute() !== false && $stmt_ratarata->rowCount() > 0) {
                                    $total_kenaikan = 0;
                                    foreach ($stmt_ratarata as $b => $row) {
                                        if($row['status'] == '+'){
                                            $total_kenaikan = $total_kenaikan + $row['perubahan'];
                                        }else{
                                            $total_kenaikan = $total_kenaikan - $row['perubahan'];
                                        }
                                    }
                                    
                                    $saham['ratarata'][$item] = $ratarata = round(abs($total_kenaikan / ($b + 1)),3);
                                    if($total_kenaikan < 0){
                                        $saham['status'][$item] = $status = '-';
                                    }else{
                                        $saham['status'][$item] = $status = '+';
                                    }
                                    
                                    $stmt_ratarata = $this->db->prepare($input_ratarata);
                                    $stmt_ratarata->bindParam(':kategori', $kategori);
                                    $stmt_ratarata->bindParam(':item', $item);
                                    $stmt_ratarata->bindParam(':tgl', $tgl);
                                    $stmt_ratarata->bindParam(':perubahan', $ratarata);
                                    $stmt_ratarata->bindParam(':status', $status);
                                    if ($stmt_ratarata->execute() == false) {
                                        $kondisi = 'Error';
                                    }
                                }
                            } 
                        }
                    }
                }
            }
            
            return $response->withJson($result, 200);
        } catch (PDOException $e) {
            return $response->withJson(array(
                "status" => "PDOException",
                "message" => $e->getMessage()
            ), 200);
        }
    });
    
    $app->get('/pencatatan/properti/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "SELECT * FROM kekayaan WHERE id = :itemId AND user_id = :user_id";

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
    
    $app->get('/pencatatan/saham/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "SELECT * FROM kekayaan WHERE id = :itemId AND user_id = :user_id";

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
    
    $app->put('/pencatatan/properti/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        $body = json_decode($request->getBody());
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktu_kepemilikan = $body->waktu_kepemilikan;

        try {
            $sql = "UPDATE kekayaan SET nama_item = :nama_item, keterangan = :keterangan, kuantitas = :kuantitas, waktu_kepemilikan = :waktu_kepemilikan WHERE id = :item_id AND user_id = :user_id";
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
    
    $app->put('/pencatatan/saham/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        $body = json_decode($request->getBody());
        $namaItem = $body->nama_item;
        $keterangan = $body->keterangan;
        $kuantitas = $body->kuantitas;
        $waktu_kepemilikan = $body->waktu_kepemilikan;

        try {
            $sql = "UPDATE kekayaan SET nama_item = :nama_item, keterangan = :keterangan, kuantitas = :kuantitas, waktu_kepemilikan = :waktu_kepemilikan WHERE id = :item_id AND user_id = :user_id";
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
    
    $app->delete('/pencatatan/properti/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "DELETE FROM kekayaan WHERE id = :item_id AND user_id = :user_id";
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
    
    $app->delete('/pencatatan/saham/{id}', function ($request, $response, $args) {
        $itemId = $args['id'];
        $userId = $request->getQueryParams()['user_id'];

        try {
            $sql = "DELETE FROM kekayaan WHERE id = :item_id AND user_id = :user_id";
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
        $tgl = date("d-m-Y");

        try {
            $sql = "INSERT INTO kekayaan (user_id, kategori, nama_item, keterangan, kuantitas, waktu_kepemilikan, waktu, tanggal) VALUES (:user_id, :kategori, :nama_item, :keterangan, :kuantitas, :waktu_kepemilikan, :waktu, :tanggal)";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':nama_item', $namaItem);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':kuantitas', $kuantitas);
            $stmt->bindParam(':waktu_kepemilikan', $waktuKepemilikan);
            $stmt->bindParam(':waktu', $waktu);
            $stmt->bindParam(':tanggal', $tanggal);

            if ($stmt->execute() !== false) {
                
                $sql_cek = "SELECT kuantitas, waktu_kepemilikan FROM kekayaan WHERE user_id = :userId AND kategori = :kategori";
                $jumlah = 0;
                $waktu_kepemilikan = array();
                if($kategori == 'Emas'){
                    $stmt_cek = $this->db->prepare($sql_cek);
                    $stmt_cek->bindParam(':user_id', $userId);
                    $stmt_cek->bindParam(':kategori', $kategori);
                    if ($stmt_cek->execute() !== false && $stmt_cek->rowCount() > 0) {
                        foreach($stmt_cek as $a => $val){
                            $jumlah += $val['kuantitas'];
                            $waktu_kepemilikan[$a] = strtotime(str_replace('/', '-', $val['waktu_kepemilikan']));
                        }
                        rsort($waktu_kepemilikan);
                        if($jumlah > 85){
                            $sql_emas = "SELECT * FROM harga_emas WHERE tanggal = :tgl";
                            $stmt_emas = $this->db->prepare($sql_emas);
                            $stmt_emas->bindParam(':tgl', $tgl);
                            if ($stmt_emas->execute() !== false && $stmt_emas->rowCount() == 0) {
                                $url = "https://www.indogold.id/harga-emas-hari-ini";
                                require_once("../util/simple_html_dom.php");
                                $html = new simple_html_dom();
                                $html->load_file($url);
                    
                                $element = $html->find('.Rectangle-price')[0];
                                $harga_emas = $element->children(0)->children(1)->children(2)->plaintext;
                                $harga_emas = preg_replace('/\D/', '', $harga_emas);
                                
                                $sql_emas_last = "SELECT harga FROM harga_emas ORDER BY id DESC LIMIT 1";
                                $stmt_emas_last = $this->db->prepare($sql_emas_last);
                                if ($stmt_emas_last->execute() !== false && $stmt_emas_last->rowCount() > 0) {
                                    if($harga_emas > $result_emas['harga']){
                                        $perubahan = round(($harga_emas - $result_emas['harga']) / $result_emas['harga'],3);
                                        $status = '+';
                                    }else{
                                        $perubahan = round(($result_emas['harga'] - $harga_emas) / $result_emas['harga'],3);
                                        $status = '-';
                                    }
                                    
                                    $input_emas = "INSERT INTO harga_emas (tanggal, harga, perubahan, status, sumber) VALUES (:tanggal, :harga, :perubahan, :status, :sumber)";
                                    $stmt_input_emas = $this->db->prepare($input_emas);
                                    $stmt_input_emas->bindParam(':tanggal', $tgl);
                                    $stmt_input_emas->bindParam(':harga', $harga_emas);
                                    $stmt_input_emas->bindParam(':perubahan', $perubahan);
                                    $stmt_input_emas->bindParam(':status', $status);
                                    $stmt_input_emas->bindParam(':sumber', $url);
                        
                                    if ($stmt_input_emas->execute() == false) {
                                        return $response->withJson(array(
                                            "status" => "failed",
                                            "message" => "Penambahan item gagal!"
                                        ), 200);
                                    }
                                }
                            }else{
                                $result_emas = $stmt_emas->fetchAll(PDO::FETCH_ASSOC);
                                
                                $harga_emas = $result_emas['harga'];
                            }
                            
                            $now = strtotime('now');
                            $tgl_zakat = strtotime("+12 month",strtotime($waktu_kepemilikan[0]));
                            $nilai_zakat = $jumlah * $harga_emas * 0.025;
                            
                            if($tgl_zakat < $now){
                                $tgl_zakat = strtotime("+12 month",strtotime($tgl_zakat));
                            }
                            
                            
                            $sql_perkiraan_zakat = "SELECT id FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                            $stmt_perkiraan_zakat = $this->db->prepare($sql_perkiraan_zakat);
                            $stmt_perkiraan_zakat->bindParam(':user_id', $userId);
                            $stmt_perkiraan_zakat->bindParam(':kategori', $kategori);
                            if ($stmt_perkiraan_zakat->execute() !== false && $stmt_perkiraan_zakat->rowCount() > 0) {
                                $result_zakat = $stmt_perkiraan_zakat->fetch(PDO::FETCH_ASSOC);
                                
                                $id = $result_zakat['id'];
                                
                                $sql_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :item";
                                $stmt_zakat = $this->db->prepare($sql_zakat);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y', $tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                $stmt_zakat->bindParam(':item', $id);
                                if ($stmt_zakat->execute()) {
                                    $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".$tgl_zakat." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() !== false) {
	                                	return $response->withJson(array(
                                            "status" => "success",
                                            "message" => "Penambahan item berhasil!"
                                        ), 200);
	                                }else{
	                                    return $response->withJson(array(
                                            "status" => "failed",
                                            "message" => "Penambahan item gagal!"
                                        ), 200);
	                                }
                                }else{
                                    return $response->withJson(array(
                                        "status" => "failed",
                                        "message" => "Penambahan item gagal!"
                                    ), 200);
                                }
                            }else{
                                $sql_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tanggal, :zakat)";
                                $stmt_zakat = $this->db->prepare($sql_zakat);
                                $stmt_zakat->bindParam(':userId', $userId);
                                $stmt_zakat->bindParam(':kategori', $kategori);
                                $stmt_zakat->bindParam(':tanggal', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y', $tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                if ($stmt_zakat->execute() !== false) {
                                    $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".$tgl_zakat." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() !== false) {
	                                	return $response->withJson(array(
                                            "status" => "success",
                                            "message" => "Penambahan item berhasil!"
                                        ), 200);
	                                }else{
	                                    return $response->withJson(array(
                                            "status" => "failed",
                                            "message" => "Penambahan item gagal!"
                                        ), 200);
	                                }
                                }else{
                                    return $response->withJson(array(
                                        "status" => "failed",
                                        "message" => "Penambahan item gagal!"
                                    ), 200);
                                }
                            }
                        }
                    }else{
                        return $response->withJson(array(
                            "status" => "failed",
                            "message" => "Penambahan item gagal!"
                        ), 200);
                    }
                }elseif($kategori == 'Perak'){
                    $stmt_cek = $this->db->prepare($sql_cek);
                    $stmt_cek->bindParam(':user_id', $userId);
                    $stmt_cek->bindParam(':kategori', $kategori);
                    if ($stmt_cek->execute() !== false && $stmt_cek->rowCount() > 0) {
                        foreach($stmt_cek as $a => $val){
                            $jumlah += $val['kuantitas'];
                            $waktu_kepemilikan[$a] = strtotime(str_replace('/', '-', $val['waktu_kepemilikan']));
                        }
                        rsort($waktu_kepemilikan);
                        if($jumlah > 595){
                            $url = "https://www.indogold.id/harga-emas-hari-ini";
                            require_once("../util/simple_html_dom.php");
                            $html = new simple_html_dom();
                            $html->load_file($url);
                            $element = $html->find('.Rectangle-price')[0];
                            $harga_perak = $element->children(0)->children(2)->children(2)->plaintext;
                            $harga_perak = preg_replace('/\D/', '', $harga_perak);
                            
                            $now = strtotime('now');
                            $tgl_zakat = strtotime("+12 month",strtotime($waktu_kepemilikan[0]));
                            $nilai_zakat = $jumlah * $harga_perak * 0.025;
                            
                            if($tgl_zakat > $now){
                                $tgl_zakat = strtotime("+12 month",strtotime($tgl_zakat));
                            }
                            
                            
                            $sql_perkiraan_zakat = "SELECT id FROM perkiraan_zakat WHERE user_id = :userId AND kategori = :kategori";
                            $stmt_perkiraan_zakat = $this->db->prepare($sql_perkiraan_zakat);
                            $stmt_perkiraan_zakat->bindParam(':user_id', $userId);
                            $stmt_perkiraan_zakat->bindParam(':kategori', $kategori);
                            if ($stmt_perkiraan_zakat->execute() !== false && $stmt_perkiraan_zakat->rowCount() > 0) {
                                $result_zakat = $stmt_perkiraan_zakat->fetch(PDO::FETCH_ASSOC);
                                
                                $id = $result_zakat['id'];
                                
                                $sql_zakat = "UPDATE perkiraan_zakat SET tanggal = :tgl, tgl_zakat = :tgl_zakat, zakat = :zakat WHERE id = :item";
                                $stmt_zakat = $this->db->prepare($sql_zakat);
                                $stmt_zakat->bindParam(':tgl', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y', $tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                $stmt_zakat->bindParam(':item', $id);
                                if ($stmt_zakat->execute()) {
                                    $message = "Pembayaran zakat untuk tabungan perak kamu wajib membayar zakat pada tanggal ".$tgl_zakat." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() !== false) {
	                                	return $response->withJson(array(
                                            "status" => "success",
                                            "message" => "Penambahan item berhasil!"
                                        ), 200);
	                                }else{
	                                    return $response->withJson(array(
                                            "status" => "failed",
                                            "message" => "Penambahan item gagal!"
                                        ), 200);
	                                }
                                }
                            }else{
                                $sql_zakat = "INSERT INTO perkiraan_zakat (user_id, kategori, tanggal, tgl_zakat, zakat) VALUES (:userId, :kategori, :tanggal, :zakat)";
                                $stmt_zakat = $this->db->prepare($sql_zakat);
                                $stmt_zakat->bindParam(':userId', $userId);
                                $stmt_zakat->bindParam(':kategori', $kategori);
                                $stmt_zakat->bindParam(':tanggal', $tgl);
                                $stmt_zakat->bindParam(':tgl_zakat', date('d-m-Y', $tgl_zakat));
                                $stmt_zakat->bindParam(':zakat', $nilai_zakat);
                                if ($stmt_zakat->execute() !== false) {
                                    $message = "Pembayaran zakat untuk tabungan emas kamu wajib membayar zakat pada tanggal ".$tgl_zakat." senilai ".rupiah($nilai_zakat);
                            		$stmt_notif = $this->db->prepare("INSERT INTO notifikasi (user_id, notifikasi) VALUES (:user_id, :notifikasi)");
                            		$stmt_notif->bindParam(':user_id', $userId);
	                                $stmt_notif->bindParam(':notifikasi', $message);
	                                if($stmt_notif->execute() !== false) {
	                                	return $response->withJson(array(
                                            "status" => "success",
                                            "message" => "Penambahan item berhasil!"
                                        ), 200);
	                                }else{
	                                    return $response->withJson(array(
                                            "status" => "failed",
                                            "message" => "Penambahan item gagal!"
                                        ), 200);
	                                }
                                }
                            }
                        }
                    }else{
                        return $response->withJson(array(
                            "status" => "failed",
                            "message" => "Penambahan item gagal!"
                        ), 200);
                    }
                }
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
            $sql = "SELECT * FROM kekayaan WHERE kategori = :kategori AND user_id = :user_id ORDER BY id DESC";

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
            $sql = "SELECT * FROM kekayaan WHERE id = :itemId AND user_id = :user_id";

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
            $sql = "UPDATE kekayaan SET nama_item = :nama_item, keterangan = :keterangan, kuantitas = :kuantitas, waktu_kepemilikan = :waktu_kepemilikan WHERE id = :item_id AND user_id = :user_id";
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
            $sql = "DELETE FROM kekayaan WHERE id = :item_id AND user_id = :user_id";
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