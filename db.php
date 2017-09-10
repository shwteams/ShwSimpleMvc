<?php
    error_reporting(E_ALL ^ E_DEPRECATED);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
    ob_start("ob_gzhandler");
    if (!isset($_SESSION)) {
	  session_start();
	}
	$tables = '*';
    try
    {
        $db = new PDO('mysql:host=localhost;dbname=db_gracedivine;charset=utf8','root','');
        $db ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
    catch(PDOException $e)
    {
        die("Erreur de connexion à la base de données : ".$e -> getMessage());
    }
	function Is_ajax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'];
    }
	/*SECURITE CONTRE LES FAILLES CRSS, 
		POUR CE PROTEGER CONTRE CE TYPE DE FAILLE IL FAUT CREER UN TOCKEN ET LE PASSER EN VARIABLE DE PORTE SESSION AU DEBUT DE NOTRE FORMULAIRE
		ENSUITE GRACE AU FORMULAIRE PASSER LE TOCKEN ET LES AUTRES DONNEES DU FORMULAIRE
		SUR LA PAGE DE SOUMISSION, VERIFIER QUE LE TOCKEN PASSE EN SESSION EST BIEN LE MEME QUE CELUI ENVOYE PAR LE FORMULAIRE, SI C4EST PAS LE MM ALORS IL Y A ERREUR, SI C'est LE MEME ALORS CA PASSE
		*Cette fonction est à utiliser sur mes pages de formulaire
	*/
	function get_tocken()
	{
		$_SESSION['tocken'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		return $_SESSION['tocken'];
	}
/**
	1- il faut lui passer en parametre la valeur du tocken générer lors de l'apparution du formulaire et passer en input de type hidden
	2- Cette fonction est à utiliser sur nos page de soumission avant même de vérifier que nos champs on bien été remplit
*/
	function is_tocken($frmHiddenTocken)
	{
		if(isset($_SESSION['tocken']) && !empty($_SESSION['tocken'])  && $_SESSION['tocken'] == $frmHiddenTocken)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
    //les fonctions de l'applications
    function select_app($db, $req, $parametre)
    {
        try
        {
            $req = $db -> prepare($req);
            $req -> execute($parametre);
            $data_query = $req -> fetch();
            $req -> closeCursor();
            return $data_query;
        }
        catch(Exception $e)
        {
           die("Une erreur c'est produite pendant la mise à jours : ".$e->getMessage());
        }
    }
	function select_row_count($db, $req, $parametre)
    {
        try
        {
            $req = $db -> prepare($req);
            $req -> execute($parametre);
            $data_query = $req -> rowCount();
            return $data_query;
        }
        catch(Exception $e)
        {
           die($e->getMessage());
        }
    }
    function select_for_fetch($db, $req, $parametre)
    {
        try
        {
            $req = $db -> prepare($req);
            $req -> execute($parametre);
            return $req;
        }
        catch(Exception $e)
        {
            die($e->getMessage());
        }   
    }
    function update_app($db, $req, $parametre)
    {
        try
        {
			$db -> beginTransaction(); 
            $req = $db -> prepare($req);
            $res['query'] = $req -> execute($parametre);
			$res['last_id'] = $db->lastInsertId();
			$db -> commit(); 
			return $res;
        }
        catch(Exception $e)
        {
			$db->rollback(); 
        	//print "Error!: " . $e->getMessage() . "</br>"; 
            die($e->getMessage());
        }
    }
	//security
	function select_user_level($db, $login, $password_user)
    {
        try
        {
            $sql = "SELECT login_user, password_user, level_user FROM user WHERE login_user = '$login' AND password_user = '$password_user' ";
            $query_user = $db -> query($sql);
            $data_query_level = $query_user -> fetch();
            return $data_query_level['level_user'];
        }
        catch(Exception $e)
        {
            die($e -> getMessage());
        }
    }
    function select_user_id($db, $login, $password_user)
    {
        try
        {
            $sql = "SELECT id_user FROM user WHERE login_user = '$login' AND password_user = '$password_user' ";
            $query_user = $db -> query($sql);
            $data_query_level = $query_user -> fetch();
            return $data_query_level['id_user'];
        }
        catch(Exception $e)
        {
            die($e -> getMessage());
        }
    }
    function verif_exit_user($db, $login, $password_user)
    {
        try
        {
            $sql = "SELECT login_user, password_user, level_user FROM user WHERE login_user = '$login' AND password_user = '$password_user' ";
            $query_user = $db -> query($sql);
            $data_query_select_user = $query_user -> fetch();
            if( $data_query_select_user['login_user'] == "$login" && $data_query_select_user['password_user'] == "$password_user" )
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        catch(Exception $e)
        {
            die($e -> getMessage());
        }
    }
	//backup
	function backup_tables($db,$login,$level,$id,$tables = '*')
	{	
		//get all of the tables
		if($tables == '*')
		{
			$tables = array();
            try
            {
                $result = $db -> query('SHOW TABLES');   
            }
            catch(Exception $e)
            {
                die('Error : '.$e -> getMessage());
            }
			while($row = $result -> fetch())
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}
		$return=NULL;
		foreach($tables as $table)
		{
			$req = "SELECT * FROM ".$table;
			try
			{
				$result = $db -> query($req);
			}
			catch(Exception $e)
			{
				die('Error : '.$e -> getMessage());
			}
			$num_fields = $result -> columnCount();
			try
			{
				$query = $db -> query('SHOW CREATE TABLE '.$table);
				$row2 = $query -> fetch();
			}
			catch(Exception $e)
			{
				die('Erreur : '.$e -> getMessage());
			}
			$return.= "\n\n".$row2[1].";\n\n";
			for ($i = 0; $i < $num_fields; $i++)
			{
				while($row = $result -> fetch())
				{
					$return.= 'INSERT INTO '.$table.' VALUES(';
					for($j=0; $j<$num_fields; $j++)
					{
						$row[$j] = addslashes($row[$j]);
						$row[$j] = preg_replace("#\n#","\\n",$row[$j]);
						if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
						if ($j<($num_fields-1)) { $return.= ','; }
					}
					$return.= ");\n";
				}
			}
			$return.="\n\n\n";
		}
		$path = 'backup/shadowWorker-BackupDb-'.date("d-m-Y").'-'.(md5(implode(',',$tables))).'.sql';
		$filename = $path;
		if (!file_exists($filename)) 
		{
			$handle = fopen($path,'w+');
			fwrite($handle,$return);
			fclose($handle);
		} 
        $pathtxt = 'backup/shadowWorker-USER-'.date("d-m-Y").'.txt';
        $filenametxt = $pathtxt;
        $connect = " \n\n\r";
        $connect .= ' USER ID : '.$id;
        $connect .= " \n\r";
        $connect .= ' USER LOGIN : '.$login;
        $connect .= " \n\r";
        $connect .= ' LEVEL : '.$level;
        $connect .= " \n\r";
        $connect .= ' COMPUTER NAME : '.php_uname('n');
		$connect .= " \n\r";
		$connect .= ' NAVIGATEUR : '.$_SERVER['HTTP_USER_AGENT'];
		$connect .= " \n\r";
		$connect .= ' PAGE VISITE : '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $connect .= PHP_EOL;
        if(file_exists($filenametxt))
        {
            $file = fopen($filenametxt, 'a+');
            fwrite($file,$connect);
            fclose($file);            
        }
        else
		{
            $file = fopen($filenametxt, 'a+');
            fwrite($file,$connect);
            fclose($file);
            $req = "";
            $parametre = array();
            $query_select_offre = select_for_fetch($db, $req, $parametre);
            while( $data_query_select_offer = $query_select_offre -> fetch() )
            {
                $num_bmc = $data_query_select_offer['num_bmc_offre'];
                $fichier_joint = $data_query_select_offer['script_pj_offre'];
                $nbr_jour = (int) $data_query_select_offer['nbr_jour'];
                $req = "SELECT login_user, name_user, email_user FROM user WHERE level_user = ?";
                $parametre = array("3");
                $data_query_user = select_app($db, $req, $parametre);
                $email = $data_query_user['email_user'];
                $user_name = $data_query_user['name_user'];
                $login = $data_query_user['login_user'];
                if(!preg_match("#^[a-z0-9._-]+@(hotmail|live|msn).[a-z]{2,4}$#", $email)) 
                    $passage_ligne = "\r\n";
                else
                    $passage_ligne = "\n";
				
                $message_html = "<html><head></head><body>Y'ello <b>$user_name</b>,<br> Cette offre expire dans <b>$nbr_jour jour(s)</b> sont numéro BMC est : <b>$num_bmc</b>. <i>Merci d'utiliser le <b>PRICEBOOK</b></i>.<br><br/><b>PS :</b>Ci-joint la note interne.<br><br>Cordialement, l'administrateur !</body></html>";
                $fichier   = fopen("offer/".$fichier_joint, "r");
                $attachement = fread($fichier, filesize("offer/".$fichier_joint));
                $attachement = chunk_split(base64_encode($attachement));
                fclose($fichier);
                $boundary = "-----=".md5(rand());
                $boundary_alt = "-----=".md5(rand());
                $sujet = "OFFRE BIENTÔT EXPIREE ($num_bmc)";
                $header = "From: \"PRICEBOOK\"<jean-romaric.moroko@mtn.ci>".$passage_ligne;
                $header.= "Reply-to: \"PRICEBOOK\"<jean-romaric.moroko@mtn.ci>".$passage_ligne;
                $header.= "MIME-Version: 1.0".$passage_ligne;
                $header.= "Content-Type: multipart/mixed;".$passage_ligne." boundary=\"$boundary\"".$passage_ligne;
                $message = $passage_ligne."--".$boundary.$passage_ligne;
                $message.= "Content-Type: multipart/alternative;".$passage_ligne." boundary=\"$boundary_alt\"".$passage_ligne;
                $message.= $passage_ligne."--".$boundary_alt.$passage_ligne;
                $message.= $passage_ligne."--".$boundary_alt.$passage_ligne;
                $message.= "Content-Type: text/html; charset=\"UTF-8\"".$passage_ligne;
                $message.= "Content-Transfer-Encoding: 8bit".$passage_ligne;
                $message.= $passage_ligne.$message_html.$passage_ligne;
                $message.= $passage_ligne."--".$boundary_alt."--".$passage_ligne;
                $message.= $passage_ligne."--".$boundary.$passage_ligne;
                $message.= "Content-Type: image/jpeg; name=\"internal_memo.pdf\"".$passage_ligne;
                $message.= "Content-Transfer-Encoding: base64".$passage_ligne;
                $message.= "Content-Disposition: attachment; filename=\"$fichier_joint\"".$passage_ligne;
                $message.= $passage_ligne.$attachement.$passage_ligne.$passage_ligne;
                $message.= $passage_ligne."--".$boundary."--".$passage_ligne; 
                mail($email,$sujet,$message,$header);
            }
		}
        $result -> closeCursor();
        $query -> closeCursor();
	}
function convert_number_to_words($number) {
   
    $hyphen      = '-';
    $conjunction = ' et '; //' et ';
    $separator   = ', ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'un',
        2                   => 'deux',
        3                   => 'trois',
        4                   => 'quatre',
        5                   => 'cinq',
        6                   => 'six',
        7                   => 'sept',
        8                   => 'huit',
        9                   => 'neuf',
        10                  => 'dix',
        11                  => 'onze',
        12                  => 'douze',
        13                  => 'treize',
        14                  => 'quatorze',
        15                  => 'quinze',
        16                  => 'seize',
        17                  => 'dix sept',
        18                  => 'dix huit',
        19                  => 'dix neuf',
        20                  => 'vingt',
        30                  => 'trente',
        40                  => 'quarante',
        50                  => 'cinquante',
        60                  => 'soixante',
        70                  => 'soixante-dix',
        80                  => 'quatre-vingt',
        90                  => 'quatre-vingt dix',
        100                 => 'cent',
        1000                => 'mille',
        1000000             => 'million',
        1000000000          => 'milliard',
        1000000000000       => 'billion',
        1000000000000000    => 'billiard'
    );
   
    if (!is_numeric($number)) {
        return false;
    }
   
   if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }
   
    $string = $fraction = null;
   
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
   
    switch (true) {
        case $number < 17:
            $string = $dictionary[$number];
            break;
		case $number < 20:
			$string = $dictionary[10];
			$un = $number - 10;
			$string .= $hyphen . $dictionary[$un];
            break;
		case $number < 21:
			$string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
			if (($tens == 70) || ($tens == 90))
			{
				$un = $tens-10;
				$string = $dictionary[$un];
				$units+=10;
			}
			else
			{
				$string = $dictionary[$tens];
            }
			if ($units) {
				if ($units == 1)
					$string .= $conjunction . $dictionary[$units];
				else
					$string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
			if ($hundreds >= 2) 
				$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
			else
				$string = $dictionary[100];
            if ($remainder) {
                $string .=' '. convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= ' ';//$remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }

   
    return $string;
}
?>