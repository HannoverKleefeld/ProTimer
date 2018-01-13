<?php
define('IPS_VERSION',4.4);
const 
	TRT_INTERVAL = 1,
	TRT_REPEATS  = 2,
	TRT_DELAFTER = 3,
	TRT_STOPIF	= 4,
	TRT_STARTIF = 5;

const 
	COND_EQUAL 		 = 0, // == 
	COND_NOTEQUAL	 = 1, // !=
	COND_GRATER		 = 2, // >
	COND_GREATE_EQUAL= 3, // >=
	COND_SMALER		 = 4, // < 
	COND_SMALER_EQUAL= 5 // <=
;

const 
	VAR_UPDATE 	= 0, //	Bei Variablenaktualisierung
	VAR_CHANGE 	= 1, // Bei Variablenänderung
	VAR_GREATER	= 2, //	Bei Grenzüberschreitung. Grenzwert wird über IPS_SetEventTriggerValue festgelegt
	VAR_SMALLER	= 3, //	Bei Grenzunterschreitung. Grenzwert wird über IPS_SetEventTriggerValue festgelegt
	VAR_USER	= 4; //	Bei bestimmtem Wert. Wert wird über IPS_SetEventTriggerValue festgelegt

class ProTimer extends IPSModule {
	function MessageSink ( $Zeitstempel, $SenderID, $NachrichtID, $Daten ){
		if($NachrichtID==11202){ // 11202	Script wurde ausgeführt
			$NachrichtID='Event %s runned : data=> %s';
			$info=json_decode(IPS_GetInfo($SenderID),true);
// IPS_LogMessage(__CLASS__,sprintf($NachrichtID,$SenderID,implode(',',$Daten)));
			if(!empty($info)){
				$stop=$info[TRT_REPEATS]==1 || IPS_GetEvent($SenderID)['EventLimit']==1;
				if($info[TRT_STOPIF])foreach($info[TRT_STOPIF] as $if){
					list($objectID,$condition,$value)=$if;
					if($v=@IPS_GetValue($objectID)){
						if( ( $condition==COND_EQUAL && $v == $value) ||
							( $condition==COND_NOTEQUAL && $v != $value) ||
							( $condition==COND_GRATER && $v > $value)  ||
							( $condition==COND_GREATE_EQUAL && $v >= $value) ||
							( $condition==COND_SMALER && $v < $value)  ||
							( $condition==COND_SMALER_EQUAL && $v <= $value) ) continue;
						$stop=false;
						break;
					}
				}
				if($stop && $info[TRT_DELAFTER]){
					usleep(100000);
					$this->SendDebug(__FUNCTION__,sprintf($NachrichtID,$SenderID,implode(',',$Daten)),0);
					IPS_DeleteEvent($SenderID);
					return;
				}
			}
		}elseif($NachrichtID==10802){ // 10802	Ereignis wurde entfernt
			$NachrichtID='Event %s deleted';
			$this->UnRegisterMessage($SenderID,11202); // 11202	Script wurde ausgeführt
		}else 
			$NachrichtID='Unknown MessageID %s with data %s'; 
 		$this->SendDebug(__FUNCTION__,sprintf($NachrichtID,$SenderID,implode(',',$Daten)),0);
	}
	function GetConfigurationForm(){
		$values=null;
		foreach(IPS_GetEventList() as $eventId){
			if(!($info=IPS_GetInfo($eventId)) && IPS_GetParent($eventId)!=$this->InstanceID)continue;
			if(!$info)$info[TRT_REPEATS]='?';
			$event=IPS_GetEvent($eventId);
			$values[]=[
				'ID'=>$eventId,
				'NAME'=>IPS_GetName($eventId),
				'INTERVAL'=>$event['EventType']==1?$event['CyclicTimeValue']:'auto',
				'REPEATS'=>$event['EventType']==0 && $info[TRT_REPEATS]==1?'auto':$info[TRT_REPEATS],
				'REST'=>$event['EventType']==1?$event['EventLimit']-1:'--',
				'NEXT'=>$event['NextRun']? date('H:m:s',$event['NextRun']):($event['EventType']==0 ?'auto':'finish'),
				'rowColor'=>($event['NextRun']!=0||$event['EventType']==0) ? '#C0FFC0': ( $info[TRT_REPEATS]=='?' ? '#DFDFDF':'#FFFFC0')
			];
		}
		if($values)$form["actions"][]=["type"=>"List","name"=>'Events',"caption"=>"Active Events","rowCount"=>5,"columns"=>[
					["label"=>"ID", "name"=>"ID","width"=>"50px"],
					["label"=>"Name","name"=>"NAME","width"=>"auto"],
	 				["label"=>"Interval", "name"=>"INTERVAL","width"=>"60px"],
					["label"=>"Repeats", "name"=>"REPEATS","width"=>"60px"],
					["label"=>"Rest", "name"=>"REST","width"=>"50px"],
					["label"=>"Next", "name"=>"NEXT","width"=>"60px"],
				],"values"=>$values 
		];else $form["actions"][]=["type"=> "Label", "label"=>"No compatible events found!"];
		$form["actions"][]=["type"=>"Button","label"=>"TEST", "onClick"=>"TIMER_test(\$id);"];
		return json_encode($form);
	}	
 	public function Test(){
		//$Timer=(new Timer('testname','echo "halloTimer";'))->every(15,4);
		$Timer=(new Timer('testbool','echo "halloTimer";'))->Once(30,false)->RunIf(10042, VAR_USER, true);
		$this->Build($Timer);
 		
 	}
	public function Once(string $Name, int $RunTimeSec, string $Script){
		return $this->Build( (new Timer($Name,$Script))->Once($RunTimeSec));
	}
	public function Every(string $Name, int $RunTimeSec, string $Script, int $Repeats, bool $DeleteIfFinishd){
		return $this->Build( (new Timer($Name,$Script))->Every($RunTimeSec,$Repeats,$DeleteIfFinishd));
	}
 	public function Build(Timer $Timer){
		$start=true; 
		$data=$Timer->Get();
 		if(IPS_VERSION < 4.4){
			if($data[TRT_STARTIF])foreach($data[TRT_STARTIF] as $if){
				list($objectID,$condition,$value)=$if;
				if($v=@IPS_GetValue($objectID)){
					if( ( $condition==COND_EQUAL && $v == $value) ||
						( $condition==COND_NOTEQUAL && $v != $value) ||
						( $condition==COND_GRATER && $v > $value)  ||
						( $condition==COND_GREATE_EQUAL && $v >= $value) ||
						( $condition==COND_SMALER && $v < $value)  ||
						( $condition==COND_SMALER_EQUAL && $v <= $value) ) continue;
					$start=false;
					break;
				}// else $stop=false;
 			}
 		}
 		if(!$start) return false;
 		$RunTimeSec=60;$Repeats=$data[TRT_STARTIF]?0:1; $eventType= $data[TRT_STARTIF]?0:1;
 		if(!$TimerId=@$this->GetIDForIdent($Timer->Name)){
 			$TimerId = IPS_CreateEvent($eventType);
 			if(empty($data[TRT_INTERVAL]))$data[TRT_INTERVAL]=$RunTimeSec;
			if(empty($data[TRT_REPEATS]))$data[TRT_REPEATS]=$Repeats;
			$this->SendDebug(__FUNCTION__,sprintf( 'Create Timer: %s for %s seconds with %s repeats', $Timer->Name,$data[TRT_INTERVAL],$data[TRT_REPEATS]),0);			
			IPS_SetIdent($TimerId, $Timer->Name);
			IPS_SetName($TimerId, $Timer->Name);
			if($data[TRT_STARTIF]){
				// IPS_SetEventCondition(12345, 0, 0, x);  x = 0=and 1=or 2=nand 3 nor
				$trigger=array_shift($data[TRT_STARTIF]);
				list($objectID,$condition,$value)=$trigger;
// 				$vartype=IPS_GetVariable($trigger[0])['VariableType'];
				IPS_SetEventTrigger ($TimerId,$condition,$objectID );
				if($condition==VAR_USER){
					IPS_SetEventTriggerValue ($TimerId, $value );							
				}
				if(count($data[TRT_STARTIF])>0){
					IPS_SetEventTriggerSubsequentExecution ( $TimerId, true );
					IPS_SetEventCondition($TimerId, 0, 0, 0);
					foreach($data[TRT_STARTIF] as $index=>$if){
						list($objectID,$condition,$value)=$if;
						IPS_SetEventConditionVariableRule($TimerId, 0, $index,  $objectID, $condition, $value);
	 				}
				}	
			}
			IPS_SetEventScript($TimerId, $Timer->Script);
			$this->RegisterMessage($TimerId,11202); // 11202	Script wurde ausgeführt
			$this->RegisterMessage($TimerId,10802); // 10802	Ereignis wurde entfernt
			IPS_SetParent($TimerId, $this->InstanceID);
 		}else{
 			if(empty($data[TRT_INTERVAL])||(empty($data[TRT_REPEATS])&&$eventType==1) ){
  				if($info=json_decode(IPS_GetInfo($this->InstanceID),true)){
					$RunTimeSec=$info[TRT_INTERVAL];
					$Repeats=$info[TRT_REPEATS];
				}
				if(empty($data[TRT_INTERVAL]))$data[TRT_INTERVAL]=$RunTimeSec;
				if(empty($data[TRT_REPEATS]))$data[TRT_REPEATS]=$Repeats;
			}
			
			$this->SendDebug(__FUNCTION__,sprintf( 'Update Timer: %s for %s seconds with %s repeats', $Timer->Name,$data[TRT_INTERVAL],$data[TRT_REPEATS]),0);			
 		}
 		unset($data[TRT_STARTIF]);
		IPS_SetInfo($TimerId,json_encode($data));
		if($eventType==1){
			IPS_SetEventCyclic($TimerId, 0 /*Daily*/, 0 /*Int*/,0 /*Days*/,0 /*DayInt*/,$Timer->TimeType /*TimeType Sec*/,$data[TRT_INTERVAL] /*Sec*/);
			$data[TRT_REPEATS]++;
		}
		IPS_SetEventLimit ( $TimerId, $data[TRT_REPEATS]);	
		
		IPS_SetEventActive($TimerId, true);
		
		return true;
 		
 	}
}

/*
 * Helper Class for TIMER_Build
 */
class Timer {
	public $Name;
	public $Script;
	public $TimeType=1; // 1 = Sec, 2 = min, 3 = hour
	function __construct(string $Name, string $Script){
		$this->Name=$Name;
		$this->Script=$Script;
	}
	function Once(int $RunTimeSec,  $DelAfter=true){
		$this->Interval=$RunTimeSec;
		$this->Repeats=1;
		$this->DeleteAfter=$DelAfter;
		return $this;
	}
	function Every(int $EveryTimeSec, $MaxRunCount=0, $DelAfter=false){
		$this->Interval=$EveryTimeSec;
		$this->Repeats=$MaxRunCount; 
		$this->DeleteAfter=(bool)$DelAfter;
		return $this;
	}
	
	function RunIf(int $ObjectID, int $Condition, $Value){
		$this->Conditions[TRT_STARTIF][]=[$ObjectID, $Condition, $Value];
		$this->Repeats=0;
		return $this;
	}
	function StopIf(int $ObjectID, int $Condition, $Value){
		$this->Conditions[TRT_STOPIF][]=[$ObjectID, $Condition, $Value];
		return $this;
	}
	function Get(){
		return [TRT_INTERVAL=>$this->Interval,TRT_REPEATS=>$this->Repeats,TRT_DELAFTER=>$this->DeleteAfter,	TRT_STARTIF=>$this->Conditions[TRT_STARTIF],TRT_STOPIF=>$this->Conditions[TRT_STOPIF]];
	}
	private $Interval=0;
	private $Repeats=0;
	private $DeleteAfter=true;
	private $Conditions=[TRT_STARTIF=>null,TRT_STOPIF=>null];
	
}

/*
 * Helper functions
 */
if(!function_exists('IPS_GetSumary')){
	function IPS_GetInfo(int $ObjectID){
		return IPS_GetObject($ObjectID)['ObjectInfo'];
	}
}

?>