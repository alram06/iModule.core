<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 템플릿 클래스로 iModule 의 모든 템플릿을 처리한다.
 * 이 클래스는 각 템플릿 파일에서 $Templet 변수로 접근할 수 있다.
 * 
 * @file /classes/Templet.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160907
 */
class Templet {
	/**
	 * iModule 코어클래스
	 */
	private $IM;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * 각 템플릿에서 이 클래스를 호출하였을 경우 사용되는 템플릿정보
	 *
	 * @private string $templetPath 템플릿 절대경로
	 * @private string $templetDir 템플릿 상대경로
	 * @private string $templetPackage 템플릿 package.json 정보
	 * @private object $templetConfigs 템플릿 환경설정 정보
	 */
	private $templetPath = null;
	private $templetDir = null;
	private $templetPackage = null;
	private $templetConfigs = null;
	
	/**
	 * 템플릿을 불러왔을 경우
	 *
	 * @private object $caller 템플릿을 요청한 객체클래스
	 * @private string $loaded 불러온 템플릿명
	 */
	private $loaded = false;
	
	/**
	 * 에러발생여부
	 */
	private $isError = false;
	
	/**
	 * 호출한 대상정보
	 */
	private $caller = null;
	private $callerType = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param object $path 템플릿 경로정보
	 * @see /classes/iModule.class.php
	 */
	function __construct($IM,$path=null) {
		/**
		 * iModule 코어 선언
		 */
		$this->IM = $IM;
	}
	
	/**
	 * [코어] 모듈을 불러온다.
	 *
	 * @param object $caller 템플릿 객체를 요청한 클래스
	 * @return Module $templet 템플릿명
	 */
	function load($caller,$templet) {
		/**
		 * $caller 에 따라 템플릿 경로를 지정한다.
		 */
		$this->callerType = get_class($caller);
		
		if ($this->callerType == 'iModule') {
			$this->templetPath = __IM_PATH__.'/templets/'.$templet;
			$this->templetDir = __IM_DIR__.'/templets/'.$templet;
		}
		
		if ($this->callerType == 'Module') {
			/**
			 * 사이트템플릿에 종속되어 있는 위젯템플릿일 경우
			 */
			if (preg_match('/^@/',$templet) == true) {
				$temp = explode('.',preg_replace('/^@/','',$templet));
				
				/**
				 * 사이트템플릿명이 없을 경우, 현재 사이트의 템플릿을 사용한다.
				 */
				if (count($temp) == 1) {
					$siteTemplet = $this->IM->getSite()->templet;
					$moduleTemplet = $temp[0];
				} else {
					$siteTemplet = $temp[0];
					$moduleTemplet = $temp[1];
				}
				
				$this->templetPath = __IM_PATH__.'/templets/'.$siteTemplet.'/modules/'.$caller->getName().'/'.$moduleTemplet;
				$this->templetDir = __IM_DIR__.'/templets/'.$siteTemplet.'/modules/'.$caller->getName().'/'.$moduleTemplet;
			} else {
				$this->templetPath = $caller->getPath().'/templets/'.$templet;
				$this->templetDir = $caller->getDir().'/templets/'.$templet;
			}
		}
		
		if ($this->callerType == 'Widget') {
			/**
			 * 위젯이 로드된 상태가 아니라면
			 */
			if ($caller->getName() == false) return $this;
			
			/**
			 * 사이트템플릿에 종속되어 있는 위젯템플릿일 경우
			 */
			if (preg_match('/^@/',$templet) == true) {
				$temp = explode('.',preg_replace('/^@/','',$templet));
				/**
				 * 사이트템플릿명이 없을 경우, 현재 사이트의 템플릿을 사용한다.
				 */
				if (count($temp) == 1) {
					$siteTemplet = $this->IM->getSite()->templet;
					$widgetTemplet = $temp[0];
				} else {
					$siteTemplet = $temp[0];
					$widgetTemplet = $temp[1];
				}
				
				$this->templetPath = __IM_PATH__.'/templets/'.$siteTemplet.'/widgets/'.$caller->getName().'/'.$widgetTemplet;
				$this->templetDir = __IM_DIR__.'/templets/'.$siteTemplet.'/widgets/'.$caller->getName().'/'.$widgetTemplet;
			} else {
				/**
				 * 모듈에 종속된 위젯의 경우
				 */
				if ($caller->getClass() !== null) {
					$temp = explode('.',$caller->getName());
					$this->templetPath = $caller->getClass()->getModule()->getPath().'/widgets/'.$temp[1].'/templets/'.$templet;
					$this->templetDir = $caller->getClass()->getModule()->getDir().'/widgets/'.$temp[1].'/templets/'.$templet;
				} else {
					$this->templetPath = __IM_PATH__.'/widgets/'.$caller->getName().'/templets/'.$templet;
					$this->templetDir = __IM_DIR__.'/widgets/'.$caller->getName().'/templets/'.$templet;
				}
			}
		}
		
		$this->caller = $caller;
		
		if (is_file($this->templetPath.'/package.json') == true) $this->loaded = $templet;
		
		return $this;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->isLoaded() === true && $this->lang == null) {
			if (is_file($this->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getPackage()->language && is_file($this->getPath().'/languages/'.$this->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getPath().'/languages/'.$this->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		/**
		 * 언어셋 텍스트가 없는경우 호출한 객체 클래스에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif ($this->caller != null && $this->callerType == 'Module') return $this->caller->getClass()->getText($code,$replacement);
		elseif ($this->caller != null && $this->callerType == 'Widget') return $this->caller->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 템플릿목록을 요청한 객체의 전체 템플릿 목록을 가져온다.
	 *
	 * @param object $caller 템플릿목록을 요청한 객체
	 * @return Templet[] $templet 템플릿목록
	 */
	function getTemplets($caller) {
		$templets = array();
		
		if ($this->callerType == 'iModule') {
			$templetsPath = @opendir(__IM_PATH__.'/templets');
			
			while ($templetName = @readdir($templetsPath)) {
				if ($templetName != '.' && $templetName != '..' && is_dir(__IM_PATH__.'/templets/'.$templetName) == true) {
					$templet = $this->IM->getTemplet($caller,$templetName);
					if ($templet->isLoaded() === true) $templets[] = $templet;
				}
			}
			@closedir($templetsPath);
		}
		
		if ($this->callerType == 'Module') {
			if ($caller->getName() == false) return array();
			
			$templetsPath = @opendir($caller->getPath().'/templets');
			
			while ($templetName = @readdir($templetsPath)) {
				if ($templetName != '.' && $templetName != '..' && is_dir($caller->getPath().'/templets/'.$templetName) == true) {
					$templet = $this->IM->getTemplet($caller,$templetName);
					if ($templet->isLoaded() === true) $templets[] = $templet;
				}
			}
			@closedir($templetsPath);
			
			$siteTemplets = @opendir(__IM_PATH__.'/templets');
			while ($siteTemplet = @readdir($siteTemplets)) {
				if ($siteTemplet != '.' && $siteTemplet != '..' && is_dir(__IM_PATH__.'/templets/'.$siteTemplet.'/modules/'.$caller->getName()) == true) {
					$templetsPath = @opendir(__IM_PATH__.'/templets/'.$siteTemplet.'/modules/'.$caller->getName());
					while ($templetName = @readdir($templetsPath)) {
						if ($templetName != '.' && $templetName != '..' && is_dir(__IM_PATH__.'/templets/'.$siteTemplet.'/modules/'.$caller->getName().'/'.$templetName) == true) {
							$templet = $this->IM->getTemplet($caller,'@'.$siteTemplet.'.'.$templetName);
							if ($templet->isLoaded() === true) $templets[] = $templet;
						}
					}
					@closedir($templetsPath);
				}
			}
			@closedir($siteTemplets);
		}
		
		return $templets;
	}
	
	/**
	 * 템플릿을 로드했는지 확인한다.
	 *
	 * @return boolean $isLoaded
	 */
	function isLoaded() {
		return $this->loaded !== false;
	}
	
	/**
	 * 현재 템플릿명을 반환한다.
	 *
	 * @param boolean $isFolderName 템플릿의 최종폴더명만 반환할것인지 여부 (템플릿명이 @siteTemplet.templet 일때 templet 만 반환)
	 * @return string $name 템플릿명
	 */
	function getName($isFolderName=false) {
		if ($this->loaded === false) return null;
		return $isFolderName == true ? preg_replace('/^@(.*?\.)?/','',$this->loaded) : $this->loaded;
	}
	
	/**
	 * 현재 템플릿의 package.json 에 정의된 컨테이너 이름을 반환한다. (container)
	 *
	 * @return string $name 컨테이너명
	 */
	function getContainerName() {
		if ($this->loaded === false) return '';
		$package = $this->getPackage();
		if (isset($package->container) == true && strpos($package->container,'.') === 0) return ' class="'.substr($package->container,1).'"';
		if (isset($package->container) == true && strpos($package->container,'#') === 0) return ' id="'.substr($package->container,1).'"';
		return '';
	}
	
	/**
	 * 템플릿 타이틀을 반환한다.
	 *
	 * @return string $title 템플릿 타이틀
	 */
	function getTitle() {
		$package = $this->getPackage();
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $this->title->{$package->language};
	}
	
	/**
	 * 템플릿의 환경설정값을 설정한다.
	 *
	 * @param object $values 환경설정값
	 * @return Templet $this
	 */
	function setConfigs($values) {
		if ($values === null) return $this;
		
		$configs = $this->getConfigs();
		foreach ($values as $key=>$value) {
			$configs->{$key}->value = $value;
		}
		
		$this->templetConfigs = $configs;
		
		return $this;
	}
	
	/**
	 * 템플릿의 전체 환경설정을 반환한다.
	 *
	 * @return object $templetConfigs
	 */
	function getConfigs() {
		if ($this->templetConfigs !== null) return $this->templetConfigs;
		
		$this->templetConfigs = new stdClass();
		$package = $this->getPackage();
		if (isset($package->configs) == true) {
			foreach ($package->configs as $key=>$value) {
				$this->templetConfigs->$key = new stdClass();
				$this->templetConfigs->$key->name = $key;
				$this->templetConfigs->$key->type = $value->type;
				$this->templetConfigs->$key->title = isset($value->title->{$this->IM->language}) == true ? $value->title->{$this->IM->language} : $value->title->{$package->language};
				$this->templetConfigs->$key->help = isset($value->help->{$this->IM->language}) == true ? $value->help->{$this->IM->language} : $value->help->{$package->language};
				$this->templetConfigs->$key->value = isset($value->default) == true ? $value->default : '';
			}
		}
		
		return $this->templetConfigs;
	}
	
	/**
	 * 템플릿의 환경설정값을 반환한다.
	 *
	 * @param string $key 환경설정키값
	 * @return string $value 환경설정값
	 */
	function getConfig($key) {
		$configs = $this->getConfigs();
		if (isset($configs->$key) == true) return $configs->{$key}->value;
		return '';
	}
	
	/**
	 * 템플릿에 존재하는 레이아웃 목록을 반환한다.
	 *
	 * @return object[] $layouts
	 */
	function getLayouts() {
		$package = $this->getPackage();
		
		$layouts = array();
		if (isset($package->layouts) == true) {
			foreach ($package->layouts as $layout=>$description) {
				if (is_file($this->getPath().'/layouts/'.$layout.'.php') == true) {
					$layouts[] = array(
						'layout'=>$layout,
						'description'=>isset($description->{$this->IM->language}) == true ? $description->{$this->IM->language} : ''
					);
				}
			}
		}
		
		return $layouts;
	}
	
	/**
	 * 템플릿에서 이용하는 변수를 정리한다.
	 *
	 * @param object[] $values 템플릿 호출시 넘어온 변수목록 (일반적으로 get_defined_vars() 함수결과가 넘어온다.
	 * @return object $values 정리된 변수
	 */
	function getValues($values=array()) {
		if (is_array($values) == true) {
			unset($values['this'],$values['IM'],$values['Module'],$values['Widget'],$values['Templet'],$values['header'],$values['footer']);
			return (object)$values;
		}
		
		return $values;
	}
	
	/**
	 * 템플릿의 package.json 정보를 반환한다.
	 *
	 * @return object $package package.json 정보
	 */
	function getPackage() {
		if ($this->loaded === false) return null;
		if ($this->templetPackage != null) return $this->templetPackage;
		
		$this->templetPackage = json_decode(file_get_contents($this->templetPath.'/package.json'));
		return $this->templetPackage;
	}
	
	/**
	 * 템플릿의 절대경로를 반환한다.
	 *
	 * @return string $path 템플릿 절대경로
	 */
	function getPath() {
		return $this->templetPath;
	}
	
	/**
	 * 템플릿의 상대경로를 반환한다.
	 *
	 * @return string $path 템플릿 상대경로
	 */
	function getDir() {
		return $this->templetDir;
	}
	
	/**
	 * 에러메세지를 반환한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @param boolean $isError 컨텍스트 전체 에러메세지인지 여부
	 * @return $html 에러메세지 HTML
	 */
	function getError($code,$value=null,$isError=true) {
		/**
		 * 이미 에러메세지가 출력된 상태라면, 다음 에러메세지는 출력하지 않는다.
		 */
		if ($this->isError === true) return '';
		
		/**
		 * iModule 코어를 통해 에러메세지를 구성한다.
		 */
		$this->isError = $isError;
		$error = $this->getErrorText($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 템플릿 헤더를 불러온다.
	 *
	 * @param object $values 템플릿에 사용되는 변수들
	 * @return string $html 헤더 HTML
	 */
	function getHeader($values=array()) {
		/**
		 * 템플릿을 불러오지 못했을 경우 에러메세지를 출력한다.
		 */
		if ($this->isLoaded() === false) return $this->getError('NOT_FOUND_TEMPLET',$this->getDir());
		
		/**
		 * 템플릿의 package.json 에 styles 나 scripts 가 설정되어 있다면, 해당 파일을 불러온다.
		 */
		$package = $this->getPackage();
		if (isset($package->styles) == true && is_array($package->styles) == true) {
			foreach ($package->styles as $style) {
				$style = preg_match('/^(http:\/\/|https:\/\/|\/\/)/',$style) == true ? $style : $this->getDir().$style;
				$this->IM->addHeadResource('style',$style);
			}
		}
		
		if (isset($package->scripts) == true && is_array($package->scripts) == true) {
			foreach ($package->scripts as $script) {
				$script = preg_match('/^(http:\/\/|https:\/\/|\/\/)/',$style) == true ? $script : $this->getDir().$script;
				$this->IM->addHeadResource('script',$script);
			}
		}
		
		$this->callerType = $this->callerType;
		$values = $this->getValues($values);
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('beforeGetHeader',$this->caller->getName(),'header',$values,null);
		
		$html = '';
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		$IM = $this->IM;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		if (is_file($this->getPath().'/header.php') == true) {
			ob_start();
			INCLUDE $this->getPath().'/header.php';
			$html.= ob_get_contents();
			ob_clean();
		}
		
		/**
		 * iModule 코어에서 호출했다면, iModule 기본 header 를 추가한다.
		 */
		if ($this->callerType == 'iModule') {
			ob_start();
			INCLUDE __IM_PATH__.'/includes/header.php';
			$header = ob_get_contents();
			ob_clean();
			
			$html = $header.PHP_EOL.$html;
		}
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('afterGetHeader',$this->caller->getName(),'header',$values,null,$html);
		
		return $html;
	}
	
	/**
	 * 템플릿 푸터를 불러온다.
	 *
	 * @param object $values 템플릿에 사용되는 변수들
	 * @return string $html 헤더 HTML
	 */
	function getFooter($values=array()) {
		/**
		 * 템플릿을 불러오지 못했을 경우 푸터를 반환하지 않는다.
		 */
		if ($this->isLoaded() === false) return '';
		
		$values = $this->getValues($values);
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('beforeGetFooter',$this->caller->getName(),'footer',$values,null);
		
		$html = '';
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		$IM = $this->IM;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		if (is_file($this->getPath().'/footer.php') == true) {
			ob_start();
			INCLUDE $this->getPath().'/footer.php';
			$html.= ob_get_contents();
			ob_clean();
		}
		
		/**
		 * iModule 코어에서 호출했다면, iModule 기본 footer 를 추가한다.
		 */
		if ($this->callerType == 'iModule') {
			ob_start();
			INCLUDE __IM_PATH__.'/includes/footer.php';
			$footer = ob_get_contents();
			ob_clean();
			
			$html = $html.PHP_EOL.$footer;
		}
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('afterGetFooter',$this->caller->getName(),'footer',$values,null,$html);
		
		return $html;
	}
	
	/**
	 * 컨텍스트를 템플릿의 특정 레이아웃에 담는다.
	 *
	 * @param string $layout 레이아웃명
	 * @param string $context 레이아웃에 담을 컨텍스트 HTML
	 * @return string $html 레이아웃 HTML
	 */
	function getLayout($layout,$context) {
		/**
		 * 템플릿폴더에 레이아웃 파일이 없다면 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/layouts/'.$layout.'.php') == false) return $this->getError('NOT_FOUND_LAYOUT',$this->getDir().'/layouts/'.$layout.'.php');
		
		/**
		 * 레이아웃파일에서 사용할 변수선언
		 */
		$IM = $this->IM;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		ob_start();
		INCLUDE $this->getPath().'/layouts/'.$layout.'.php';
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * 템플릿 컨텍스트를 가져온다.
	 *
	 * @param string $file PHP 확장자를 포함하지 않는 템플릿 컨텍스트 파일명
	 * @param string $values 템플릿 호출시 넘어온 변수목록 (일반적으로 get_defined_vars() 함수결과가 넘어온다.
	 * @param string $header(옵션) 컨텍스트 HTML 상단에 포함할 헤더 HTML
	 * @param string $footer(옵션) 컨텍스트 HTML 하단에 포함할 푸더 HTML
	 * @param string $layout(옵션) 컨텍스트를 담을 템플릿 레이아웃
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext($file,$values=array(),$header='',$footer='',$layout=null) {
		/**
		 * 에러메세지가 출력된 상태라면, 템플릿 컨텍스트를 반환하지 않는다.
		 */
		if ($this->isError === true) return '';
		
		/**
		 * 템플릿폴더에 파일이 없다면 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/'.$file.'.php') == false) return $this->getError('NOT_FOUND_TEMPLET_FILE',$this->getDir().'/'.$file.'.php');
		
		$values = $this->getValues($values);
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('beforeGetContext',$this->caller->getName(),$file,$values,null);
		
		foreach ($values as $key=>$value) {
			if (in_array($key,array('IM','Module','Widget','Templet','header','footer','this')) == false) ${$key} = $value;
		}
		
		$html = '';
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		$IM = $this->IM;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		if (is_file($this->getPath().'/'.$file.'.php') == true) {
			$html = $header;
			
			ob_start();
			INCLUDE $this->getPath().'/'.$file.'.php';
			$html.= ob_get_clean();
			
			$html.= $footer;
		}
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		if ($this->callerType !== 'Widget') $this->IM->fireEvent('afterGetContext',$this->caller->getName(),$file,$values,null,$html);
		
		if ($layout !== null) return $this->getLayout($html);
		return $html;
	}
	
	/**
	 * 템플릿에 포함된 외부파일 가져온다.
	 *
	 * @param string $file 확장자를 포함하는 외부파일
	 * @return string $html 외부파일 HTML
	 */
	function getExternal($file) {
		/**
		 * 에러메세지가 출력된 상태라면, 템플릿 컨텍스트를 반환하지 않는다.
		 */
		if ($this->isError === true) return '';
		
		/**
		 * 템플릿폴더에 외부파일이 없다면 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/externals/'.$file) == false) return $this->getError('NOT_FOUND_EXTERNAL_FILE',$this->getDir().'/externals/'.$file);
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		$IM = $this->IM;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		if ($this->callerType == 'Module') {
			$Module = $this->caller;
			$me = $this->caller->getClass();
		}
		
		if ($this->callerType == 'Widget') {
			$Widget = $this->caller;
			if ($Widget->getClass() !== null) {
				$me = $Widget->getClass();
				$Module = $me->getModule();
			}
		}
		
		$Templet = $this;
		
		ob_start();
		INCLUDE $this->getPath().'/externals/'.$file;
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * 페이지이동 네비게이션을 가져온다.
	 *
	 * @param int $p 현재페이지
	 * @param int $total 총 페이지
	 * @param int $pagenum 페이지이동버튼 갯수
	 * @param string $link(옵션) 페이지 이동링크 (페이지번호가 들어가는 부분에 {PAGE} 치환자 사용)
	 * @param string $mode 페이지 표시 형식 (FIXED, CENTER)
	 * @param string $file(옵션) 페이지 네비게이션 템플릿 파일명 (.php 제외)
	 * @return string $html
	 */
	function GetPagination($p,$total,$pagenum,$link,$mode='LEFT',$file=null) {
		$total = $total == 0 ? 1 : $total;
	
		if ($mode == 'LEFT') {
			$startPage = floor(($p-1)/$pagenum) * $pagenum + 1;
			$endPage = $startPage + $pagenum - 1 < $total ? $startPage + $pagenum - 1 : $total;
			$prevPageStart = $startPage - $pagenum > 0 ? $startPage - $pagenum : false;
			$nextPageStart = $endPage + 1 < $total ? $endPage + 1 : false;
		} else {
			$startPage = $p - floor($pagenum/2) > 0 ? $p - floor($pagenum/2) : 1;
			$endPage = $p + floor($pagenum/2) > $pagenum ? $p + floor($pagenum/2) : $startPage + $pagenum - 1;
			$prevPageStart = null;
			$nextPageStart = null;
		}
		
		$prevPage = $p > 1 ? $p - 1 : false;
		$nextPage = $p < $total ? $p + 1 : false;
		
		$IM = $this->IM;
		$Templet = $this;
		
		
		if ($file == null) {
			ob_start();
			if (is_file($this->getPath().'/pagination.php') == true) {
				INCLUDE $this->getPath().'/pagination.php';
			} else {
				INCLUDE __IM_PATH__.'/includes/pagination.php';
			}
			$html = ob_get_clean();
		} else {
			if (is_file($this->getPath().'/'.$file.'.php') == true) {
				ob_start();
				INCLUDE $this->getPath().'/pagination.php';
				$html = ob_get_clean();
			} else {
				return $this->getError('NOT_FOUND_TEMPLET_FILE',$this->getDir().'/'.$file.'.php',false);
			}
		}
		
		return $html;
	}
	
	/**
	 * 자바스크립트용 언어셋 파일을 호출한다.
	 * 언어셋은 기본적으로 PHP파일을 통해 사용되나 모듈의 자바스크립트에서 언어셋이 필요할 경우 해당 함수를 호출하여 자바스크립트상에서 모듈명.getLanguage() 함수로 언어셋을 불러올 수 있다.
	 *
	 * @todo 템플릿용 자바스크립트 언어셋은 어떻게 처리할지 고민필요
	 */
	function loadLanguage() {
		
	}
}
?>