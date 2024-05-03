<?
class YouTubeAPI 
{
	// Properties will have got at url
	private $video_id,
			$videoTitle,
			$videoThumbnailUrl,
			$videoResolutionUrl,
			$videoResolutions,
			$messages;
	private const BASE_URL = "https://www.youtube.com/youtubei/v1/player";
	private const DEFAULT_CLIENTS = [
		'WEB'=> [
	        'context'=> [
	            'client'=> [
	                'clientName'=> 'WEB',
	                'clientVersion'=> '2.20200720.00.02'
	            ]
	        ],
	        'header'=> 'User-Agent: Mozilla/5.0',
	        'api_key'=> 'AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8'
	    ],
	    'ANDROID_MUSIC'=> [
        	'context'=> [
            	'client'=> [
	                'clientName'=> 'ANDROID_MUSIC',
	                'clientVersion'=> '5.16.51',
	                'androidSdkVersion'=> 30
            	]
        	],
	        'header'=> 'User-Agent: com.google.android.apps.youtube.music/',
        	'api_key'=> 'AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8'
    	],
    	'WEB_EMBED'=> [
	        'context'=> [
	            'client'=> [
	                'clientName'=> 'WEB_EMBEDDED_PLAYER',
	                'clientVersion'=> '2.20210721.00.00',
	                'clientScreen'=> 'EMBED'
	            ]
	        ],
	        'header'=> 'User-Agent: Mozilla/5.0',
	        'api_key'=> 'AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8'
	    ],
	    'ANDROID_EMBED'=> [
	        'context'=> [
	            'client'=> [
	                'clientName'=> 'ANDROID_EMBEDDED_PLAYER',
	                'clientVersion'=> '17.31.35',
	                'clientScreen'=> 'EMBED',
	                'androidSdkVersion'=> 30,
	            ]
	        ],
	        'header'=> 
	            'User-Agent: com.google.android.youtube/'
	        ,
	        'api_key'=> 'AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8'
	    ]
	];
	private function id_parser($req) 
	{
		// video identificator parsed in request url
		$li = fn($x) => $x[array_key_last($x)];
		$lie = function(&$x, $y){$x[array_key_last($x)]=$y;};
		$ex = fn ($x, $y) => explode($x, $y);
		$req = $ex("/", $req);
		$lie($req, $ex("?", $li($req)));
		if ($req[2] == "youtu.be")
		{
			return $req[3][0];
		}
		if (str_contains($req[2], "youtube.com"))
		{
			if (
				is_array($req[3]) ? $req[3][0] == "watch" : False
			)
			{
				$lie($req[3], $ex("&", $li($req[3])));
				foreach ($req[3][1] as $i) {
			        if (str_contains($i, "v=")) {
			            return explode("v=", $i)[1];
			        }
			    }
			}
			if ( is_array($req[4]) ? $req[3] == "shorts" : False )
			{ return $req[4][0]; }	
		}
		throw new Exception(
			$this->messages->Exceptions->id_parser
		);
	}
	private function getVideoData(&$headers,&$base_data,&$query,&$videoData,$client)
	{
		$headers = [
		    YouTubeAPI::DEFAULT_CLIENTS[$client]['header'],
		    'accept-language: en-US,en',
		    'Content-Type: application/json'
		];
		$base_data = [
		    'context'=> YouTubeAPI::DEFAULT_CLIENTS[$client]['context'],
		];
		$query = [
		    'videoId'=> $this->video_id,
		    'key'=> YouTubeAPI::DEFAULT_CLIENTS[$client]['api_key'],
		    'contentCheckOk'=> 'true',
		    'racyCheckOk'=> 'true'
		];
		$ch = curl_init(YouTubeAPI::BASE_URL."?".http_build_query($query));
		curl_setopt_array($ch, [
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => json_encode($base_data),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => $headers
		]);
		$videoData = json_decode(curl_exec($ch));
		if (!property_exists($videoData, "videoDetails"))
		{
			throw new Exception(
				$this->messages->Exceptions->id_parser
			);
		}
		curl_close($ch);
	}
	function __construct($req, $messages, $is_url = true)
	{
		// Getting messages for porgram interface
		$this->messages = $messages;
		// Getting all info about video at url
		$this->video_id = $is_url ? $this->id_parser($req) : $req;
		$this->getVideoData($headers, $base_data, $query, $videoData, "WEB");
		$this->videoThumbnailUrl = end($videoData->videoDetails->thumbnail->thumbnails)->url;
		$this->videoTitle = $videoData->videoDetails->title;
		foreach (['ANDROID_EMBED',"ANDROID_MUSIC"] as $value) {
			$this->getVideoData($headers, $base_data, $query, $videoData, $value);
			if (array_key_exists("streamingData", (array)$videoData))
			{
				break;
			}
		}
		if (!array_key_exists("streamingData", (array)$videoData))
		{
			throw new Exception(
				$this->messages->Exceptions->not_available
			);
		}
		foreach ($videoData->streamingData->formats as $value)
		// adaptiveFormats haven't sound in video 
		{
		    if (isset($value->height) and isset($value->url))
		    {
		        $this->videoResolutionUrl[$value->height] = $value->url;
		    }
		}
		$this->videoResolutions = array_keys($this->videoResolutionUrl);
	}
	function &__get($var)
	{
		switch ($var) {
			case 'getVideoId':
				return $this->video_id;
				break;
			case 'getVideoTitle':
				return $this->videoTitle;
				break;
			case 'getVideoThumbnailUrl':
				return $this->videoThumbnailUrl;
				break;
			case 'getVideoResolutions':
				return $this->videoResolutions;
				break;
			case 'getVideoResolutionUrl':
				return $this->videoResolutionUrl;
				break;
			case 'getVideoResolutionTGButton':
				return $this->videoResolutionTGButton;
				break;
		}
	}
	function download($inputData, &$obj, $Resolution)
	{
		$headers = [
		    "User-Agent: Mozilla/5.0",
		    "accept-language: en-US,en"
		];
		$chat_id = $inputData->callback_query->message->chat->id;
		$responseHeaders = [];
		// Getting file size
		for ($i=0; $i < 100; $i++) { 
			$ch = curl_init($this->videoResolutionUrl[$Resolution]."&range=0-99999999999");
			curl_setopt_array($ch, [
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_NOBODY => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_TIMEOUT => 180,
				CURLOPT_HEADERFUNCTION => 
				function($curl, $header) use (&$responseHeaders)
				{	
					$item = explode(":", $header, 2);
					if (str_contains($header, 'HTTP') || $item[0] == "\r\n" )
					{
						return strlen($header);
					}
					$responseHeaders[$item[0]] = rtrim($item[1]);
					return strlen($header);
				}
			]);
			curl_exec($ch);
			if (!empty($responseHeaders))
			{
				if ($responseHeaders["Content-Length"] != 0) break;
			}
		}
		curl_close($ch);

		// File settings
		$file_size = $responseHeaders["Content-Length"];
		$file_name = $chat_id."_".time().".mp4";
		$file = fopen("S:\\temp\\".$file_name, "w+");
		$default_range_size = 9437184; // 9MB
		$downloaded = 0;
		$stop_pos = 0;
		$end_pos = $file_size - 1;
		$message_id = $obj->sendMessage(
			$chat_id,
			$this->messages->Preparing.": 0%"
		);
		// Write video in file
		while ($stop_pos != $end_pos) {
			$stop_pos = min($downloaded + $default_range_size, $file_size) - 1;
			$url = $this->videoResolutionUrl[$Resolution]."&range=$downloaded-$stop_pos";
			$ch = curl_init($url);
			curl_setopt_array($ch, [
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPHEADER => $headers
			]);
			echo $stop_pos."<br>";
			$chunk = curl_exec($ch);
			fwrite($file, $chunk);
			$downloaded += strlen($chunk);
			$complete = (int)($downloaded / $end_pos * 100);
			$obj->editMessage(
				$chat_id,
				$message_id,
				$this->messages->Preparing.": {$complete}%"
			);
			curl_close($ch);
		}
		fclose($file);
		$obj->deleteMessage($chat_id, $message_id);
		return $file_name;
	}
}
?>