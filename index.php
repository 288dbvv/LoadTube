<?
ini_set('max_execution_time', '-1');
include_once "tgAPI.php";
include_once "YouTubeAPI.php";


$inputData = json_decode(file_get_contents("php://input", true));
$messages = json_decode(file_get_contents("messages.json"));
$token = "6395992664:AAGg5_ecXZiorb-WYdaMZMlMUOKlJ3CGWOo";
$TG_BOT = new TG_API($token);

if (isset($inputData->message->text))
{
	$commands = (array)$messages->Commands;
	if (
		array_key_exists(
			$inputData->message->text, 
			$commands
		)
	)
	{
		$TG_BOT->sendMessage(
			$inputData->message->from->id,
			$commands[$inputData->message->text]
		);
	}
	else {
		$url = $inputData->message->text;
		try
		{
			$video = new YouTubeAPI($url, $messages);
			$TG_BOT->sendVideoInfo(
				$inputData->message->from->id,
				$video->getVideoTitle,
				$video->getVideoThumbnailUrl,
				$video->getVideoId
			);
		}
		catch (Exception $e)
		{
			$TG_BOT->sendMessage(
				$inputData->message->from->id,
				$e->getMessage()
			);
		}
	}
}

if (isset($inputData->callback_query->data))
{
	$res = explode(" ", $inputData->callback_query->data);
	$id = $res[1];
	$video = new YouTubeAPI($id, $messages, false);
	if ($res[0] == "get")
	{
		$TG_BOT->pressButton(
			$inputData->callback_query->message->chat->id,
			$inputData->callback_query->message->message_id,
			$id,
			$video->getVideoResolutionUrl
		);
	}
	if ($res[0] == "download")
	{
		$TG_BOT->removeButtons(
			$inputData->callback_query->message->chat->id,
			$inputData->callback_query->message->message_id,
		);
		$file_name = $video->download($inputData, $TG_BOT, end($video->getVideoResolutions));
		$TG_BOT->sendVideo(
			$inputData->callback_query->message->chat->id, 
			$file_name, 
			$video->getVideoTitle, "S:\\temp\\".$file_name
		);
		unlink("S:\\temp\\".$file_name);
	}
}
?>
