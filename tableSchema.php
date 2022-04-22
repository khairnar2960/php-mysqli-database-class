<?php
/**
 * 
 */
class Model{
	private $fields;
	private $engine = "InnoDB";
	private $charset = "utf8mb4";
	private $collate = "utf8mb4_unicode_ci";
	private $stmt = null;
	public function CreateModel(){

	}
	
	public function getNull($null=null){
		if ($null===null) {
			return "NULL";
		}else{
			return "NOT NULL";
		}
	}
	public function getDefault($default=null){
		if ($default===null || $default===false) {
			return "DEFAULT ".$this->getNull($default);
		}else{
			return "DEFAULT '{$default}'";
		}
	}
	public function getUnsigned($unsigned=false){
		if ($unsigned===true) {
			return " unsigned";
		}else{
			return;
		}
	}
	public function onDelete($on_delete){
		$on_delete = strtoupper($on_delete);
		if ($on_delete=="CASCADE") {
			return "ON DELETE CASCADE";
		}elseif ($on_delete=="PROTECT"){
			return "ON DELETE PROTECT";
		}elseif ($on_delete=="RESTRICT"){
			return "ON DELETE RESTRICT";
		}else{
			throw new \Exception("ForeignKey Constraints 'ON DELETE' not provided");
		}
	}
	public function CreateField($model_name, $name, $field){
		// ALTER TABLE `sample_test` ADD `long_text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NOT NULL AFTER `varcharfield`, ADD another field
	}
	public function AlterField($model_name, $name, $field){
		//ALTER TABLE `sessions` CHANGE `id` `session_id` VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, CHANGE `access` `session_expires` BIGINT UNSIGNED NULL DEFAULT NULL, CHANGE `data` `session_data` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
	}
	public function DropField($model_name, $name, $field){
		//ALTER TABLE `sample_test` DROP `varcharfield`, DROP `notmal_text`,
	}
	public function ForeignKey($table, $on_delete="CASCADE"){
		return $this->onDelete($on_delete);
		"CONSTRAINT `states` FOREIGN KEY (`id`) REFERENCES `states` (`id`) ON DELETE CASCADE";
	}
	/**
	 * AutoIncrement fields
	 **/
	public function SmallAutoField($default=false){
		return $this->SmallIntegerField($default)." AUTO_INCREMENT";
	}
	public function AutoField($primary_key=false, $default=false){
		return $this->IntegerField($default)." AUTO_INCREMENT";
	}
	public function BigAutoField($primary_key=false, $default=false){
		return $this->BigIntegerField($default)." AUTO_INCREMENT";
	}
	public function SerialAutoField($null=false){
		return "SERIAL {$this->getNull($null)}";
	}
	/**
	 * Number fields
	 **/
	public function SmallIntegerField($default=false, $unsigned=false){
		return "smallint{$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	public function IntegerField($default=false, $unsigned=false){
		return "int{$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	public function BigIntegerField($default=false, $unsigned=false){
		return "bigint{$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	public function DecimalField($max_digits=10, $decimal_places=2, $default=false, $unsigned=false){
		return "decimal({$max_digits}, {$decimal_places}){$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	public function FloatField($max_digits=10, $decimal_places=2, $default=false, $unsigned=false){
		return "float({$max_digits}, {$decimal_places}){$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	public function DoubleField($max_digits=10, $decimal_places=2, $default=false, $unsigned=false){
		return "double({$max_digits}, {$decimal_places}){$this->getUnsigned($unsigned)} {$this->getDefault($default)}";
	}
	

	/**
	 * boolean field
	 **/
	public function BooleanField($default=false){
		return "tinyint(1) {$this->getDefault($default)}";
	}
	/**
	 * Binary Large Object (blob)
	 **/
	// max_size = 64KiB
	public function SmallBlobField($default=false){
		return "blob {$this->getDefault($default)}";
	}
	// max_size = 16MiB
	public function BlobField($default=false){
		return "mediumblob {$this->getDefault($default)}";
	}
	// max_size = 4GiB
	public function BigBlobField($default=false){
		return "longblob {$this->getDefault($default)}";
	}
	/**
	 * Date and Time fileds
	 **/
	// YYYY-MM-DD
	public function DateField($null=false){
		return "date {$this->getNull($null)}";
	}
	// hh:mm:ss
	public function TimeField($null=false){
		return "time {$this->getNull($null)}";
	}
	// YYYY-MM-DD hh:mm:ss
	public function TimeStampField($auto_now_add=false, $auto_now=false, $null=false){
		$schema = "timestamp {$this->getNull($null)} ";
		if ($auto_now_add===true) {
			$schema .= $this->getDefault("CURRENT_TIMESTAMP");
		}
		if ($auto_now===true) {
			$schema .= " ON UPDATE CURRENT_TIMESTAMP";
		}
		return $schema;
	}
	// YYYY-MM-DD hh:mm:ss
	public function DateTimeField($auto_now_add=false, $auto_now=false, $null=false){
		$schema = "datetime {$this->getNull($null)} ";
		if ($auto_now_add===true) {
			$schema .= $this->getDefault("CURRENT_TIMESTAMP");
		}
		if ($auto_now===true) {
			$schema .= " ON UPDATE CURRENT_TIMESTAMP";
		}
		return $schema;
	}


	/**
	 * string fields
	 **/
	public function CharField($max_length=256, $default=false){
		return "varchar({$max_length}) CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getDefault($default)}";
	}
	public function SmallTextField($null=false){
		return "text CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getNull($null)}";
	}
	public function TextField($null=false){
		return "mediumtext CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getNull($null)}";
	}
	public function BigTextField($null=false){
		return "longtext CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getNull($null)}";
	}
	public function SlugField(){

	}
	public function JSONField($null=false){
		return "json {$this->getNull($null)}";
	}
	public function UUIDField($primary_key=True, $default="uuid4", $editable=False){

	}
	public function ChoiceField(array $choice, $null=false){
		if ($choice!==null && is_array($choice)) {
			if (count($choice)>0) {
				return "enum('".implode("', '", $choice)."') CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getNull($null)}";
			}else{
				throw new \Exception("Choice not provided");
			}
		}else{
			throw new \Exception("Choice not provided");
		}
	}
	public function MultiChoiceField(array $choice, $null=false){
		if ($choice!==null && is_array($choice)) {
			if (count($choice)>0) {
				return "set('".implode("', '", $choice)."') CHARACTER SET {$this->charset} COLLATE {$this->collate} {$this->getNull($null)}";
			}else{
				throw new \Exception("Choice not provided");
			}
		}else{
			throw new \Exception("Choice not provided");
		}
	}
	public function SmallBinaryField($max_length=255){
		return "binary({$max_length}) {$this->getNull($null)}";
	}
	public function BinaryField($max_length=65535){
		return "varbinary({$max_length}) {$this->getNull($null)}";
	}

	public function EmailField(){

	}
	public function FileField(){

	}
	public function FilePathField(){

	}
	public function ImageField(){

	}
	// ' ENGINE=%3$s DEFAULT CHARSET=%4$s COLLATE=%5$s';
}
$stl = "CREATE TABLE IF NOT EXISTS `sessions` (
		    PRIMARY KEY (`id`),
		    KEY `state_id` (`state_id`),
		    UNIQUE(`state_id`),
		    CONSTRAINT `state_to_local` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
		) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='some comment';";

$model = new Model;
