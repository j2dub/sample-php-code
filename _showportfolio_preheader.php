<?php

	if (get_magic_quotes_gpc()) {
	    $_POST = array_map('stripslashes_deep', $_POST);
	    $_GET = array_map('stripslashes_deep', $_GET);
	    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	}

	$cid = $_GET["cid"];
	$cname = $_GET["cname"];
	$companyname = $_GET["companyname"];


	// old artist assemble code 
	/* $pattern = strpos($_SERVER['REQUEST_URI'],'.php');
	$seller_name = substr($_SERVER['REQUEST_URI'],$pattern+5,strlen($_SERVER['REQUEST_URI'])-$pattern);
	$select_artist_id = "SELECT artist_id FROM ".$tableprefix."artists WHERE artist_name = '".$seller_name."'";
	$result_artist_id = mysql_query($select_artist_id) or die(mysql_error());  
	$artist_id_row = mysql_fetch_assoc($result_artist_id);
	$artistid = $artist_id_row['artist_id']; */

	if(isset($_GET['artistname'])) {
	    $storename = mysql_real_escape_string($_GET['artistname']);
	    $sql = "SELECT artist_id, artist_name FROM ".$tableprefix."artists WHERE artist_name = '".$storename."'";
	    $result = mysql_query($sql) or die(mysql_error()); 
	    mysql_free_result($result);
	    $artistid = $row[0];
	    $artistname = $row[1];
	    $row = mysql_fetch_row($result);
	}
	elseif(isset($_GET['companyname'])) {
		    //$storename = ($_GET['companyname']);
		    $storename = replaceCompanyName($_GET['companyname']);
		    $storename_url = $_GET['companyname'];
		    $sql = "SELECT artist_id, artist_name, company_url, company, first_name, last_name FROM ".$tableprefix."artists WHERE company LIKE '%".mysql_real_escape_string($storename)."' OR company_url LIKE '%".$storename_url."' ";
		   
		    $result = mysql_query($sql) or die(mysql_error()); 
		    $row = mysql_fetch_row($result);
		    if(!isset($row[0])) {
		        echo '<script type="text/javascript"> document.location = "http://www.handmademi.com/404/";</script>'  ;
    			exit;
		    }
		    else {
		        $artistid = $row[0];
		        $artistname = $row[1];
		        $companyurl = $row[2];
		        $companyname = $row[3];
		        $firstname = $row[4];
		        $lastname = $row[5];
		    }
	     	   mysql_free_result($result);
	}
	elseif (isset($_GET["artistid"]) and $_GET["artistid"] != "") {
	    $artistid = $_GET["artistid"];
	} else if (isset($_POST["artistid"]) and $_POST["artistid"] != "") {
	    $artistid = $_POST["artistid"];
	}

	// Get artist page title
	if(isset($companyname)) { 
	  $page_title = $companyname;
	}
	else
	{
	    $sql = "SELECT artist_name FROM ".$tableprefix."artists WHERE artist_id = '".  mysql_real_escape_string($artistid)."'";
	    $result = mysql_query($sql) or die(mysql_error()); 
	    $row = mysql_fetch_row($result);
	    $page_title = $row[0];
	}


	if($enable_seller_friendly == "Y")
	{
		$select_artist_id = "SELECT artist_id,artist_name FROM ".$tableprefix."artists WHERE artist_name = ".GetSQLValueString($artistid,"text");
		$result_artist_id = mysql_query($select_artist_id) or die(mysql_error());
		
		if(mysql_num_rows($result_artist_id) > 0)
		{
			$artist_row = mysql_fetch_assoc($result_artist_id);
			$artistid = $artist_row['artist_id'];
			$artist_name = $artist_row['artist_name'];
		}
	}


	// set profile image directory
	$portfoliodir = SITE_URL."/portfolios/";
	
	$sql = "SELECT ap.*, a.artist_name,a.first_name,a.last_name,a.state, a.country, a.city, a.company  
		FROM ".$tableprefix."artists a LEFT JOIN ".$tableprefix."artist_portfolios ap ON a.artist_id = ap.artist_id  
		WHERE a.artist_id = '".addslashes($artistid)."' ";
	$res = mysql_query($sql);
	
	if(mysql_num_rows($res) > 0 ) {
		$row= mysql_fetch_array($res);
		$portfolioid = $row["artist_portfolio_id"];
		$txtArtistDescription = $row["artist_description"];
		$txtArtistPhoto= $row["artist_photo"];
		$arrSamples = array();
		
	
		$txtArtistName = $row["artist_name"];
		$txtArtistFirstName = $row["first_name"];
		$txtArtistLastName = $row["last_name"];
		$city              = $row["city"];
		$txtArtistFullName = $txtArtistFirstName;
		if($txtArtistLastName!=""){
			$txtArtistFullName .= " ". $txtArtistLastName;
		}
		$txtArtistState = $row["state"];
		$txtArtistCountry = $row["country"];
		
		// set store name
		if($row['company']!='')
			$txtCompanyName=stripslashes($row['company']);
		else
			$txtCompanyName=$txtArtistName;
		
		if($txtArtistPhoto == "")
			$imageurl = "<img src='images/no_image_lrg.jpg' width='200' height='167' border='0' >";
		else{
			if(is_file('portfolios/'.$txtArtistPhoto))
				$imageurl = "<img style='margin-bottom: 8px;' class='thumbphotos round' src='portfolios/".$txtArtistPhoto."' border='0' width='200' height='200' >";
			else
				$imageurl = "<img style='margin-bottom: 8px;' class='thumbphotos round' src='images/no_image_lrg.jpg' width='200' height='167' border='0' >";
		}
	}
	
	
	if($enable_seller_friendly == "Y")
	{
		$artist_name = $row['artist_name'];
	}
	
	
	// Get total product views count
	$sqlpvcount = "SELECT SUM(pv.views) AS TotViews FROM ".$tableprefix."productpageviews as pv INNER JOIN ".$tableprefix."products AS p ON pv.productid = p.product_id INNER JOIN ".$tableprefix."artists AS a ON p.product_artist_id = a.artist_id WHERE a.artist_id = '".addslashes($artistid)."' GROUP BY a.artist_id ";
	$respv = mysql_query($sqlpvcount);
	$row= mysql_fetch_array($respv);
	$pvcount = $row['TotViews'];
	     
    	// Get artist policies information
    	$sqlprof = "SELECT pol.*, prof.artist_description FROM ".$tableprefix."artist_policies AS pol RIGHT JOIN ".$tableprefix."artist_portfolios AS prof ON pol.artist_ID = prof.artist_id WHERE prof.artist_id = '".addslashes($artistid)."' ";
	$resprof = mysql_query($sqlprof) or die(mysql_error());
	
	if(mysql_num_rows($resprof) > 0 ){
		$row= mysql_fetch_array($resprof);
		$txtProfile = $row["artist_description"];
		$txtWelcome = $row["a_welcome"];
		$txtShipping = $row["a_shipping"];
		$txtReturns = $row["a_returns"];
		$txtAdditional = $row["a_additional"];
	}  
    
    
	// Get artist social media
	$sqlsocial = "SELECT * FROM ".$tableprefix."artist_profiles WHERE artist_id = '".addslashes($artistid)."' AND deleted = 'N' AND pr_type <> 'store' AND pr_type <> 'amz' AND pr_type <> 'etsy' AND pr_type <> 'ebay' ORDER BY created_at ";
	$ressocial = mysql_query($sqlsocial) or die(mysql_error());
	$socialprofiles = mysql_num_rows($ressocial);
	
	// Get artist facebook profile
	$sqlfb = "SELECT * FROM ".$tableprefix."artist_profiles WHERE artist_id = '".addslashes($artistid)."' AND deleted = 'N' AND pr_type = 'fb' ";
	$resfb = mysql_query($sqlfb) or die(mysql_error());
		if(mysql_num_rows($resfb) > 0 ){
			$row= mysql_fetch_array($resfb);
			$txtFB = $row["pr_data"];	
     		}


?>

<script>
function clickSearch()
{
	document.frmCatalog.submit();
}
</script>