<?
class TG_API
{
	private $token;
	private const API_URL = "https://api.telegram.org/bot";
	function __construct($token)
	{
		$this->token = $token;
	}
	private function buttonTable($id, $resArr)
	{
		/* 
			Making table of resolution video buttons
			Used in event of pressed button "Download"
		*/
		$row = []; $table = [];
		foreach ($resArr as $key => $value) {
			array_push($row, [
				'text' => $key." ⬇",
				'callback_data' => "download ".$id." ".$key
			]);
			if (count($row) == 3 or $key == array_key_last($resArr))
			{
				array_push($table, $row);
				$row = [];
			}
		}
		return $table;
	}
	private function sendRequest($ch, &$arrayQuery = Null)
	{
		if ($arrayQuery)
		{
			curl_setopt_array($ch, [
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $arrayQuery,
			]);
		}
		curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_SSL_VERIFYPEER => false,
		]);
		return curl_exec($ch);
	}
	function sendMessage($chat_id, $text)
	{
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'text' => $text,
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/sendMessage?'.http_build_query($arrayQuery));
		$txt = json_decode($this->sendRequest($ch));
		curl_close($ch);
		return $txt->result->message_id;
	}
	function editMessage($chat_id, $message_id, $text)
	{
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'message_id' => $message_id,
		    'text' => $text,
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/editMessageText');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function deleteMessage($chat_id, $message_id)
	{
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'message_id' => $message_id
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/deleteMessage');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function sendVideoInfo($chat_id, $caption, $path, $data)
	{
		// send download button
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'caption' => $caption,
		    'photo' => curl_file_create($path, 'image/jpg' , 'img.jpg'),
		    'protect_content' => true,
		    'reply_markup' => json_encode([
		    'inline_keyboard' =>
			    [
			    	[
			    		[
			    			"text" => "Download ✅",
			    			"callback_data" => "get ".$data
			    		]
			    	]
			    ]
			])
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/sendPhoto');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function sendVideo($chat_id, $file_name, $caption, $path)
	{
		// send download button
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'document' => curl_file_create($path, 'video/mp4' , $caption.'.mp4')
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/sendDocument');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function pressButton($chat_id, $message_id, $video_id, $buttons)
	{
		/*
			This is event of pressed button "Download".
			After pressing button will be send table of
			resolution video buttons
		*/
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'message_id' => $message_id,
		    'reply_markup' => json_encode([
		    'inline_keyboard' => $this->buttonTable($video_id, $buttons)
		])
		];	
		$ch = curl_init(TG_API::API_URL.$this->token.'/editMessageReplyMarkup');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function removeButtons($chat_id, $message_id)
	{
		$arrayQuery = [
		    'chat_id' => $chat_id,
		    'message_id' => $message_id,
		    'reply_markup' => json_encode([
		    'inline_keyboard' => []
		])
		];
		$ch = curl_init(TG_API::API_URL.$this->token.'/editMessageReplyMarkup');
		$this->sendRequest($ch, $arrayQuery);
		curl_close($ch);
	}
	function __get($var)
	{
		switch ($var) {
			case 'token':
				return $this->token;
				break;
		}
	}
	function __set($var, $val)
	{
		switch ($var) {
			case 'token':
				$this->token = $val;
				break;
		}
	}
}
?>