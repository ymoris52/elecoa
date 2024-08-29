<?php

    abstract class ActivityBase {
        protected $context;  // コンテキスト
        protected $strID;    // ID
        protected $strTitle; // タイトル
        // No: $actArray の要素番号
        protected $parent;   // 親の No
        protected $children; // 子の No (array)

        protected $isActive; // アクティブ
        protected $isSus;    // 中断中
        protected $aCounter;    // アクセス数

        protected $dataNode;

        protected $ignoreTrace;

        // コンストラクタ
        // $ctx:  コンテキスト
        // $num:  親の No, ルートノードなら -1
        // $node: 自分のノード (DOMNode)
        // $res:  再開なら真
        function __construct (&$ctx, $num, $node, $res, &$objectives) {
            $this->context = $ctx;
            $this->strID = $node->getAttribute('identifier');
            $snode = new SerializableNode($node);
            $n = selectSingleNode($snode, 'title');
            $this->strTitle = is_null($n) ? '' : $n->nodeValue;
            $this->dataNode = selectSingleNode($snode, 'itemData');
            $this->parent = $num;
            $this->children = array();
            $this->isActive = FALSE;
            $this->isSus = FALSE;
            $this->aCounter = 0;
            $this->ignoreTrace = FALSE;
        }

        // コンテキストのゲッタ
        public final function getContext() {
            return $this->context;
        }

        // ID のゲッタ
        public final function getID() {
            return $this -> strID;
        }

        // タイトルのゲッタ
        public final function getTitle() {
            return $this -> strTitle;
        }

        public final function isSuspend() {
            return $this->isSus;
        }

        // ステータスのゲッタ
        public function getStatus($str) {
            return NULL;
        }

        // タイプ ('ROOT', 'BLOCK', 'LEAF') のゲッタ
        public abstract function getType();

        // 子の追加
        public function addChild($num) {
            $this -> children[] = $num;
        }

        // 子オブジェクトを返す
        // $num 番目の子 = $actArray の $this -> children[$num] 番目
        protected function getChild($num) {
            $platform = Platform::getInstance();

            return $platform->getActivity($this->children[$num]);
        }

        // 子の位置 (ID が $tmpID の子は何番目か) を返す
        protected function getChildPosition($tmpID) {
            $platform = Platform::getInstance();

            $len = count($this->children);
            for ($i = 0; $i < $len; $i++) {
                if ($this->getChild($i)-> getID() === $tmpID) {
                    return $i;
                }
            }
            return -1;
        }

        /**
         * 親オブジェクトを返す。
         *
         * @return object 親アクティビティオブジェクト。存在しなければnull。
         */
        protected function getParent() {
            $platform = Platform::getInstance();
            return $platform->getParent($this->parent);
        }

        protected abstract function startAttempt();
        protected abstract function endAttempt($cmd);
        public abstract function terminate();
        
        
        /**
         * トレースオブジェクトから可読性の高い文字列を生成して返す。
         * 
         * @param array $trace トレースオブジェクト。
         * @return string トレース文字列。
         */
        private function getTraceString($trace) {
            $result = '';
            
            if (isset($trace['file'])) {
                $result .= $trace['file'] . ' ';
            }
            if (isset($trace['line'])) {
                $result .= '(' . $trace['line'] . ') ';
            }
            if (isset($trace['object'])) {
                $result .= get_class($trace['object']);
                
                if ($trace['object'] instanceof ActivityBase) {
                    $result .= '<' . $trace['object']->getID() . '>';
                }
            }
            if (isset($trace['type'])) {
                $result .= $trace['type'];
            }
            if (isset($trace['class'])) {
                $result .= $trace['class'] . '::';
            }
            if (isset($trace['function'])) {
                $result .= $trace['function'];
            }
            if (isset($trace['args'])) {
                $result .= '(';
                $is_first = TRUE;
                foreach ($trace['args'] as $arg) {
                    if (!$is_first) {
                        $result .= ', ';
                    }
                    
                    if (is_object($arg)) {
                        $result .= '[object]';
                    }
                    else if (is_array($arg)) {
                        $result .= '[';
                        $is_first_inarray = TRUE;
                        foreach ($arg as $item_key => $item_value) {
                            if (!$is_first_inarray) {
                                $result .= ', ';
                            }
                            $result .= (is_string($item_key) ? "'$item_key' => " : '') . (is_string($item_value) ? "'$item_value'" : (is_array($item_value) ? 'Array' : var_export($item_value, TRUE)));
                            $is_first_inarray = FALSE;
                        }
                        $result .= ']';
                    }
                    else if (is_string($arg)) {
                        $result .= "'$arg'";
                    }
                    else if (is_bool($arg)) {
                        $result .= $arg ? 'true' : 'false';
                    }
                    else if (is_null($arg)) {
                        $result .= 'NULL';
                    }
                    else {
                        $result .= $arg;
                    }
                    
                    $is_first = FALSE;
                }
                $result .= ')';
            }
            
            return $result;
        }

        public function ignoreTrace() {
            $this->ignoreTrace = TRUE;
        }

        function getGradeModule() {
            if ($this->ignoreTrace) {
                return null;
            } else {
                return getGradeModule();
            }
        }

        protected function makeCheckResult($result, $description) {
            return array('Result' => $result, 'Description' => $description);
        }

        protected static function isCheckResultEmpty($result) {
            return $result['Result'] === '' ? TRUE : FALSE;
        }

        protected static function isCheckResultError($result) {
            return $result['Result'] === 'error' ? TRUE : FALSE;
        }

        /**
         * ./syslog/traceに情報を出力する。
         * 
         * @param string $str 出力文字列。指定しないと呼び出しメソッド（関数）情報を出力する。
         * @param bool $output_backtrace TRUEを指定するとバックトレース情報を出力する。
         * @param bool $force_output TRUEを指定すると出力しないモードの場合でも強制的に出力する。
         */
        protected function co_trace($str = '', $output_backtrace = FALSE, $force_output = FALSE) {
            if ($force_output && !$this->ignoreTrace) {
                // 毎回 $trace_file を作るので効率が悪い

                $dirs = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
                array_pop($dirs);
                $trace_file = implode('/', $dirs) . '/syslog/trace';
                $bt = debug_backtrace();
                if ($str === '' && isset($bt[1])) {
                    $str = $this->getTraceString($bt[1]);
                }
                if ($fh = fopen($trace_file, 'a+')) {
                    fwrite($fh, date('Y/m/d H:i:s') . " $str  [ " . $this -> getID() . ' / ' . get_class($this) . " ]\n");
                    if ($output_backtrace) {
                        foreach ($bt as $trace) {
                            fwrite($fh, $this->getTraceString($trace) . "\n");
                        }
                    }
                    fclose($fh);
                }
            }
        }
    }
