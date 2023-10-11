<?php

define('TELEGRAM_API_KEY', "TOKEN");
define('MATNUZ_API_KEY', "KEY");
$db = new mysqli('localhost', 'elbekjohn', 'pass', 'iscorrect');
function check($datas = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://matn.uz/api/v1/suggestions');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . MATNUZ_API_KEY
    ));

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $new_data = [];
    foreach($datas as $data){
        $data = str_replace([
        "'", "ʻ", "’", "\n", "\t"
        ], [
            "'", "'", "'", " ", " "
        ], $data);
        $new_data[] = $data;
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "text" => json_encode($new_data)
    ]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result);
}

function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . TELEGRAM_API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

$input = json_decode(file_get_contents('php://input'));
$message = $input->message;
$text = $message->text;
$caption = $message->caption;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
if($db->query("SELECT `id` FROM `users` WHERE `user_id` = '$chat_id'")->num_rows == 0){
    $db->query("INSERT INTO `users` VALUES (NULL, '$chat_id')");
}

if($text == "/start"){
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>"*Xatolikni tekshirish uchun matn yoki so'z yuboring!*\n\nBot haqida - /help",
        'parse_mode'=>"markdown"
    ]);
}elseif($text == "/help"){
    bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>"*Bot haqida*\n\nUshbu bot kiritilgan so'zlarni imloviy xatolarga tekshirish uchun yaratildi. Bot matn.uz sayti orqali ishlaydi va bu borada matn.uz jamoasi o'z minnatdorchiligimizni bildiramiz!\nDasturchi [Elbek Khamdullaev](https://t.me/khamdullaevuz)",
        'parse_mode'=>"markdown",
        'disable_web_page_preview'=>true
    ]);
}elseif(!empty($text)){
    preg_match_all("/[a-zA-Z'ʻ’]+/", $text, $matches);
    
    $response = check($matches);
    if($response->errors){
        $incorrect = $response->data;
        $errors = "";
        foreach ($incorrect as $values) {
            $suggestions = "";
            foreach ($values->suggestions as $suggestion) {
                $suggestions .= $suggestion . ", ";
            }
            $len = strlen($suggestions);
            $suggestions = substr($suggestions, 0, $len - 2);
            $errors .= "`".$values->word . " (o'xshash: " . $suggestions . ")`\n";
        }
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>"*Xato so'zlar topildi.*\n_Bular:_\n" . $errors,
            'parse_mode'=>"markdown",
            'reply_to_message_id'=>$message_id
        ]);
    }else{
        bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>"*Xatolik topilmadi*",
        'parse_mode'=>"markdown",
            'reply_to_message_id'=>$message_id
        ]);
    }
}elseif(!empty($caption)){
    preg_match_all("/[a-zA-Z'ʻ’]+/", $caption, $matches);
    
    $response = check($matches);
    if($response->errors){
        $incorrect = $response->data;
        $errors = "";
        foreach ($incorrect as $values) {
            $suggestions = "";
            foreach ($values->suggestions as $suggestion) {
                $suggestions .= $suggestion . ", ";
            }
            $len = strlen($suggestions);
            $suggestions = substr($suggestions, 0, $len - 2);
            $errors .= "`".$values->word . " (o'xshash: " . $suggestions . ")`\n";
        }
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>"*Xato so'zlar topildi.*\n_Bular:_\n" . $errors,
            'parse_mode'=>"markdown",
            'reply_to_message_id'=>$message_id
        ]);
    }else{
        bot('sendMessage',[
        'chat_id'=>$chat_id,
        'text'=>"*Xatolik topilmadi*",
        'parse_mode'=>"markdown",
        'reply_to_message_id'=>$message_id
        ]);
    }
}
