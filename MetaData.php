<?php

class MetaData{
	// SearchEngine
	public $lang = "en_IN";
	public $title = null;
	public $sitename = null;
	public $desc = null;
	public $image = null;
	public $keywords = null;
	public $author = null;
	public $google_site_verification = null;
	public $indexFollow = true;
	// OpenGraph/Facebook
	public $article = null;
	public $og_type = null;
	public $fb_app_id = null;
	public $facebook_domain_verification = null;
	// Twitter
	public $site =null;
	public $creator =null;

	public function __construct($data=[]){
		if (array_key_exists('title', $data)) {
			$this->title = $data['title'];
		}
		if (array_key_exists('sitename', $data)) {
			$this->sitename = $data['sitename'];
		}else{
			$this->sitename = $this->siteName();
		}
		if (array_key_exists('desc', $data)) {
			$this->desc = $data['desc'];
		}
		if (array_key_exists('image', $data)) {
			$this->image = $this->img2Url($data['image']);
		}
		if (array_key_exists('author', $data)) {
			$this->author = $data['author'];
		}else{
			$this->author = $this->siteURL();
		}
		if (array_key_exists('google_site_verification', $data)) {
			$this->google_site_verification = $data['google_site_verification'];
		}
	}

	private function isoDate($date=null){
		if ($date!==null) {
			date_default_timezone_set("Asia/Kolkata");
			return date(DATE_ATOM, strtotime($date));
		}
	}
	// join 2 URL's with "/" 
	private function joinURL($url1=null,$url2=null){
		if ($url1!==null && $url2!==null) {
			return rtrim(str_replace("\\","/",$url1),"/")."/".ltrim(str_replace("\\","/",$url2),"/");
		}
	}

	// get site URL
	private function siteURL(){
		return (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http')."://".$_SERVER['SERVER_NAME'];
	}

	// get REQUEST_URI
	private function requestURI(){
		return $_SERVER['REQUEST_URI'];
	}

	// get REQUEST_URI
	private function absoluteURL(){
		return $this->joinURL($this->siteURL(), $this->requestURI());
	}

	// get site name
	private function siteName(){
		// ucfirst("string") convert first char to uppercase
		// ucwords("string") Convert the first character of each word to uppercase
		// return ucfirst(explode(".", $_SERVER['SERVER_NAME'])[0]);
		return ucwords($_SERVER['SERVER_NAME']);
	}

	// get site name touppercase
	private function siteName2Upper(){
		// strtoupper() used to conver to uppercase
		return strtoupper($this->siteName());
	}

	// concatinate site url with relative image path
	private function img2URL($img=null){
		if ($img!==null) {
			// strpos($string, "key_to_find") used to get position of key in string if exist otherwise returns null
			if (!strpos($img,"http") && strpos($img,"http")!==0) {
				return $this->joinURL($this->siteURL(), $img);
			}else{
				return $img;
			}
		}else{
			return "";
		}
	}
	// <title></title> tag
	public function pageTitle(){
		return "\t<title>{$this->title}</title>\n";
	}
	public function getAll(){
		return	$this->seoTags().
				$this->schemaTags().
				$this->twitterTags().
				$this->ogTags();
	}
	// SearchEngine Tags with keywords
	public function seoTags($keywords=null){
		if ($keywords!==null) {
			if (is_array($keywords)) {
				$this->keywords = implode(", ", $keywords);
			}else{
				$this->keywords = $keywords;
			}
		}
		$tags = "\t<!-- SEO Tags -->\n";
		if ($this->title!==null) {
			$tags .= $this->pageTitle();
			$tags .= "\t<meta name='title' content='{$this->title}'>\n";
		}
		if ($this->image!==null) {
			$tags .= "\t<meta name='image' content='{$this->image}'>\n";
		}
		if ($this->desc!==null) {
			$tags .= "\t<meta name='description' content='{$this->desc}'>\n";
		}
		if ($this->keywords!==null) {
			$tags .= "\t<meta name='keywords' content='{$this->keywords}'>\n";
		}
		if ($this->author!==null) {
			$tags .= "\t<meta name='author' content='{$this->author}'>\n";
		}
		if ($this->indexFollow===true) {
			$tags .= "\t<meta name='robots' content='index, follow'>\n";
		}else{
			$tags .= "\t<meta name='robots' content='noindex, nofollow' />\n";
		}
		if ($this->siteURL()) {
			$tags .= "\t<link rel='canonical' href='{$this->siteURL()}'>\n";
		}
		if ($this->google_site_verification!==null) {
			$tags .= "\t<meta name='google-site-verification' content='{$this->google_site_verification}' />\n";
		}
		return $tags;
	}

	// Schema tags
	public function schemaTags(){
		$tags = "\t<!-- Schema Tags -->\n";
		if ($this->title!==null) {
			$tags .= "\t<meta itemprop='name' content='{$this->title}'>\n";
		}
		if ($this->image!==null) {
			$tags .= "\t<meta itemprop='image' content='{$this->image}'>\n";
		}
		if ($this->desc!==null) {
			$tags .= "\t<meta itemprop='description' content='{$this->desc}'>\n";
		}
		return $tags;
	}

	// Twitter card tags
	public function twitterTags($site=null,$creator=null){
		if ($site!==null) {
			$this->site = $site;
		}
		if ($creator!==null) {
			$this->creator = $creator;
		}
		$tags = "\t<!-- Twitter Card Tags -->\n";
		$tags .= "\t<meta name='twitter:card' content='summary_large_image'>\n";
		$tags .= "\t<meta name='twitter:url' content='{$this->absoluteURL()}'>\n";
		if ($this->title!==null) {
			$tags .= "\t<meta name='twitter:title' content='{$this->title}'>\n";
		}
		if ($this->image!==null) {
			$tags .= "\t<meta name='twitter:image' content='{$this->image}'>\n";
		}
		if ($this->desc!==null) {
			$tags .= "\t<meta name='twitter:description' content='{$this->desc}'>\n";
		}
		if ($this->site!==null) {
			$tags .= "\t<meta name='twitter:site' content='@{$this->site}'>\n";
		}
		if ($this->creator!==null) {
			$tags .= "\t<meta name='twitter:creator' content='@{$this->creator}'>\n";
		}
		return $tags;
	}

	// openGraph / facebook tags
	public function ogTags($article=null, $og_type=null, $fb_app_id=null, $facebook_domain_verification=null){
		$tags = "\t<!-- OpenGraph/Facebook Tags -->\n";
		if ($og_type!==null) {
			$this->og_type = $og_type;
		}
		if ($fb_app_id!==null) {
			$this->fb_app_id = $fb_app_id;
		}
		if ($facebook_domain_verification!==null) {
			$this->facebook_domain_verification = $facebook_domain_verification;
		}if ($article!==null) {
			$this->article = $article;
		}
		if ($this->article!==null && count($this->article)>0) {
			$this->og_type = "article";
		}else{
			if ($this->og_type===null) {
				$this->og_type = "website";
			}
		}
		if ($this->fb_app_id!==null) {
			$tags .= "\t<meta property='fb:app_id' content='{$this->fb_app_id}' />\n";
		}
		if ($this->facebook_domain_verification!==null) {
			$tags .= "\t<meta name='facebook-domain-verification' content='{$this->facebook_domain_verification}' />\n";
		}
		if ($this->title!==null) {
			$tags .= "\t<meta property='og:title' content='{$this->title}'>\n";
		}
			$tags .= "\t<meta property='og:url' content='{$this->absoluteURL()}'>\n";
		if ($this->sitename!==null) {
			$tags .= "\t<meta property='og:site_name' content='{$this->sitename}'>\n";
		}
		if ($this->lang!==null) {
			$tags .= "\t<meta property='og:locale' content='{$this->lang}'>\n";
		}
		if ($this->desc!==null) {
			$tags .= "\t<meta property='og:description' content='{$this->desc}'>\n";
		}
		if ($this->image!==null) {
			$tags .= "\t<meta property='og:image' itemprop='image' content='{$this->image}'>\n";
		}
		if ($this->og_type!==null) {
			$tags .= "\t<meta property='og:type' content='{$this->og_type}'>\n";
		}
		if ($this->og_type==="article" && is_array($this->article)) {
			if (array_key_exists('articletag', $this->article)) {
				$tags .= "\t<meta property='article:tag' content='{$this->article["articletag"]}' />\n";
			}elseif ($this->keywords!==null){
				$tags .= "\t<meta property='article:tag' content='{$this->keywords}' />\n";
			}
			if (array_key_exists('author', $this->article)) {
				$tags .= "\t<meta property='article:author' content='{$this->article["author"]}'>\n";
			}else{
				$tags .= "\t<meta property='article:author' content='{$this->author}'>\n";
			}
			if (array_key_exists('publisher', $this->article)) {
				$tags .= "\t<meta property='article:publisher' content='{$this->article["publisher"]}'>\n";
			}
			if (array_key_exists('section', $this->article)) {
				$tags .= "\t<meta property='article:section' content='{$this->article["section"]}'>\n";
			}
			if (array_key_exists('published_time', $this->article)) {
				$tags .= "\t<meta property='article:published_time' content='{$this->isoDate($this->article["published_time"])}'>\n";
			}
			if (array_key_exists('modified_time', $this->article)) {
				$tags .= "\t<meta property='article:modified_time' content='{$this->isoDate($this->article["modified_time"])}'>\n";
			}
		}
		return $tags;
	}
}


$meta = new MetaData();
$meta->title = "Harshal Khairnar | Portfolio";
// $meta->sitename = "Harshal Khairnar";
$meta->desc = "160 char description";
$meta->image = "static/img/meta_img.png";
// $meta->author = "https://harshalkhairnar.com/";
$meta->google_site_verification = "uGWqBnagWGWB1rYUwg27i8TA796sqZ5mhCmjVls-ta8";
$meta->keywords = "portfolio, Harshal, Khairnar, Web Developer, python developer, django developer, website developer";
$meta->site = "khairnar2960";
$meta->creator = "khairnar2960";
$meta->article = [
	"author" => "https://www.instagram.com/khairnar2960/",
	"publisher" => "https://github.com/khairnar2960",
	"published_time" => "2022-01-07",
	"modified_time" => "2022-03-16",
	"section" => "Programming",
];

$meta->fb_app_id = "1313931599118305";
$meta->facebook_domain_verification = "klevhj2dfye4hcds1e53u84euxpdgl";
echo $meta->getAll();
