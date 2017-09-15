<?php
   /* 
   * respuestas a webhook de salida Rocket.Chat
   * https://tar.mx/archivo/2017/crear-un-bot-para-rocket-chat-en-php.html
   * 
   * @toro 2017 https://tar.mx/
   */
   $token=""; //establecer el token de tu webhook de salida
   $me = "techbot"; //nombre del bot
   $content = file_get_contents("php://input"); $content = json_Decode($content);
   //lee el token generado por el webhoook:
   if(!isset($content->token) || $content->token != $token) { die("No te conozco."); }
   if($content->user_name == $me) { exit(); } // me
   // solo dos comandos: busca en flicker y ayuda.
   $arg = explode(" ",$content->text);
   if(preg_match("/^ayuda/i",$arg[0])) {
      $msg = sprintf("Hola *%s* humano, esto es lo que puedo hacer:\n",$content->user_name);
      $msg .= "`ayuda` - esta ayuda\n";
      $msg .= "`busca` - busca fotos en flickr!";
      rocket($content->user_id, $msg);
   } elseif(preg_match("/^busca/i",$arg[0])) {
      if(empty($arg[1])) rocket($content->user_id,"Intenta escribiendo: *busca gatos*");
      else {
         //buscamos en flickr, sólo una palabra en tags.
         $url="https://api.flickr.com/services/feeds/photos_public.gne?tags=".$arg[1]."&format=php_serial&per_page=3&page=1";
         @$data = file_get_contents($url);
         @$data = unserialize($data);
         if(!empty($data) && isset($data['items']) && !empty($data['items'])) {
            $i=0;
            $attach = []; //enviar mensaje como attachments a rocketchat
            foreach($data['items'] AS $k=>$v) {
               $attach[$i] = [
               "title" => $v['title'],
               "title_link" => $v['url'],
               "image_url" => $v['photo_url'],
               'text' => $v['tags'],
               ];
               if($i>=0) break; //solo 1, aquí lo puedes aumentar a varios :)
               $i++;
            }
            rocket($content->user_id, null, $attach);
         } else rocket($content->user_id, "flickr no me quiere :o");
      }
   } else {
      rocket($content->user_id,"no se a que te refieras, escribe *ayuda*.");
   }
   /* rocket ( canal, mensaje, adjuntos ) {{{ */
   function rocket($ch = "#xpruebas", $msg = ":o", $attach = []) {
      $url="URL_INCOMING_WEBHOOK";
      $datas = ['msg' => $msg, 'username' => 'techbot', 'channel'=> $ch]; 
      if(!empty($attach)) $datas['attachments'] = $attach;
      $data = "payload=" . json_encode($datas);
      $ch = curl_init(); 
      curl_setopt($ch, CURLOPT_POST,1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL,$url);
      $result = curl_exec($ch);
      curl_close($ch);
      //debug
      //print_r(json_decode($result));
   } /* }}} */
