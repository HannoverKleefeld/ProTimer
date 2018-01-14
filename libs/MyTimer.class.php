<?php 
/*
 * Helper Class for TIMER_Build
 */
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

	
class MyTimer {
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
?>