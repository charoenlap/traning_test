<?php 
	class MasterModel extends db {
        public function getTicketByID($id=0){
            $result = array();
            $sql = "SELECT *,
                    `ep_status`.`status_class` as text_class,
                    `ep_status`.`status_text` as text_status,
                    `ep_response`.`id` as id  
                        FROM ep_response 
                    LEFT JOIN ep_agency_minor ON ep_response.`id_angency_minor` = ep_agency_minor.`id` 
                    LEFT JOIN ep_status ON ep_response.`status` = ep_status.`id`
                    WHERE AUT_USER_ID = '".(int)$id."'";
            $result_response = $this->query($sql); 
            foreach($result_response->rows as $key => $val){
                $id_response = $val['id'];
                $sql_agency = "SELECT *,ep_response_status.id AS id,ep_response_status.date_create AS date_create FROM ep_response_status 
                    LEFT JOIN ep_agency_minor ON ep_agency_minor.id = ep_response_status.id_agency_minor
                WHERE id_response = ".(int)$id_response;

                $result[] = array(
                    'text_status'       => $val['text_status'],
                    'text_class'        => $val['text_class'],
                    'case_code'         => $val['case_code'],
                    'response_person'   => $val['response_person'],
                    'agency'            => $this->query($sql_agency)->rows
                );
            }
            return $result;
        }
        public function login($user='', $pass=''){
            $result = array();
            $pass = md5($pass);
            $sql = "SELECT * FROM AUT_USER 
            WHERE AUT_USERNAME = '".$this->escape($user)."' AND AUT_PASSWORD='".$this->escape($pass)."'
            AND id_agency = 5 
            AND id_agency_minor=12
            AND ACTIVE_STATUS = 1
            AND DELETE_FLAG = 0";
            $result = $this->query($sql);
            return $result;
        }
        public function getPrefix($data=array()){
            $result = array();
            $sql = "SELECT * FROM ep_prefix";
            $query = $this->query($sql);
            return $query->rows;
        }
        public function getProvinces($data=array()){
            $result = array();
            $sql = "SELECT * FROM PROVINCE";
            $result = $this->query($sql)->rows;
            return $result;
        }
        public function getAmphures($data=array()){
            $result = array();
            $province_id = (int)$data['province_id'];
            $sql = "SELECT * FROM AMPHUR WHERE PROVINCE_ID = '".$province_id."'";
            $result = $this->query($sql)->rows;
            return $result;
        }
        public function getTambon($data=array()){
            $result = array();
            $amphure_id = (int)$data['amphure_id'];
            $sql = "SELECT * FROM TAMBON WHERE AMPHUR_ID = '".$amphure_id."'";
            $result = $this->query($sql)->rows;
            return $result;
        }
		public function getTicket($data = array()){
        	$result = array();
        	$case_code = (isset($data['case_code'])?$this->escape($data['case_code']):'');
        	// echo $case_code;
        	if($case_code){
	            $sql = "SELECT *,
                        `ep_status`.`status_class` as text_class,
                        `ep_status`.`status_text` as text_status,
                        `ep_response`.`id` as id  
                            FROM ep_response 
        	            LEFT JOIN ep_agency_minor ON ep_response.`id_angency_minor` = ep_agency_minor.`id` 
                        LEFT JOIN ep_status ON ep_response.`status` = ep_status.`id`
        	            WHERE case_code = '".$case_code."'";
	            $result = $this->query($sql)->row; 
                $id = $result['id'];
                $sql_agency = "SELECT *,ep_response_status.id AS id,ep_response_status.date_create AS date_create FROM ep_response_status 
                    LEFT JOIN ep_agency_minor ON ep_agency_minor.id = ep_response_status.id_agency_minor
                WHERE id_response = ".(int)$id;
                $result['agency'] = $this->query($sql_agency)->rows;
	        }
            return $result;
        }
        public function getTopicSub(){
            $result = array();
            $sql = "SELECT * FROM ep_topic WHERE topic_title !='' order by sort ASC";
            $result_topic = $this->query($sql);
            if($result_topic->num_rows){
                foreach($result_topic->rows as $val){
                    $sql_sub = "SELECT * FROM ep_topic_sub WHERE topic_id = ".(int)$val['id'];
                    $result_sub = $this->query($sql_sub);
                    $result[] = array(
                        'rows'  => $val,
                        'sub'       => $result_sub->rows
                    );
                }

            }
            return $result;
        }
        public function addResponse($data=array()){
			$day_end = 30;
			$sql_config_day = "SELECT * FROM ep_config WHERE `name` = 'day_end'";
			$query_config_day = $this->query($sql_config_day);
			if($query_config_day->num_rows){
				$day_end = $query_config_day->row['val'];
			}

            $sql_title = "SELECT * FROM ep_prefix WHERE `id` = '".(int)$data['name_title']."'";
            $query_title = $this->query($sql_title);
            if($query_title->num_rows){
                $data['name_title'] = $query_title->row['title'];
            }

			$dateadd=date('Y-m-d');
			$date_end = date('Y-m-d', strtotime($dateadd. ' + '.$day_end.' days'));
            $data['id_provinces'] = $data['provinces'];
            $data['id_amphures'] = $data['amphures'];
            $data['id_districts'] = $data['districts'];
            
			$data['day_end']	= $day_end;
			$data['dateadd']	= date('Y-m-d H:i:s'); 
			$data['date_end']	= $date_end;
			$data['status']		= 2;
			$result_last_insert = $this->insert('response',$data);
			$case_code = ((date('y')+43).date('m')).str_pad($result_last_insert,4,"0", STR_PAD_LEFT);
			$sql_update = "UPDATE ep_response SET case_code = '".$case_code."' WHERE id=".$result_last_insert;
			$query_update = $this->query($sql_update);

			return $case_code;
		}
	}