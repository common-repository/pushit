<?php
/* insert code <img src="<?php show_beetag(400);?>"> in your template
you may change parameter to your size of the beetag from 100 too 500. 
but if you want to change size of your tags you have empty folder `beetags` 
under pushit plugin folder
*/
function show_beetag($set_size = 205){
  global $post;
  if (($set_size < 100) || ($set_size > 500)){
    $set_size = 200;
  }
  $post_id = $post->ID;
  $beetag_url = "/wp-content/plugins/pushit/beetags/$post_id.png";
  $beetag_fname = dirname(__FILE__)."/beetags/$post_id.png";
  if (!file_exists($beetag_fname)){
    $content = get_option('siteurl')."/?p=$post_id";
    $c=curl_init('http://generator.beetagg.com/CodeGenerator.aspx');
    $data = 'codetype=bt&ctl00$ContentPlaceHolder1$ContentType=url&ctl00$ContentPlaceHolder1$content='.urlencode($content);
    curl_setopt($c,CURLOPT_POST, true);
    curl_setopt($c,CURLOPT_POSTFIELDS, $data);
    curl_setopt($c,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($c);
    curl_close($c);
    // <img id="ctl00_ContentPlaceHolder1_ctl03" src="http://factory.beetagg.com/compose.ashx?size=180&amp;rangeid=45531&amp;offset=1&amp;imagepath=" style="border-width:0px;" />
    $reg = '/.*?\<td align="center"\>\<img.*?src="(http:\/\/factory\.beetagg\.com\/compose\.ashx\?size=.*?)".*?\/\>.*/s';
    preg_match($reg, $html, $matches);
    $beetag_base_url = str_replace('&amp;', '&', $matches[1]);
    $reg = '~(.*?size=)([\d]+)(.*)~';
    preg_match($reg, $beetag_base_url, $matches);
    $size = $matches[2];
    $beetag_base_url = $matches[1].$set_size.$matches[3];
    if(is_writable(dirname($beetag_fname))){
      $beetag = file_get_contents($beetag_base_url);
      $beetag_file = fopen($beetag_fname, "wb");
      fwrite($beetag_file, $beetag);
      fclose($beetag_file);
    } else {
      $beetag_url = $beetag_base_url;
    }
  }
  echo $beetag_url;
}


?>
