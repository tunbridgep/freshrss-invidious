<?php

/**
 * Class InvidiousExtension
 *
 * Based on https://github.com/Korbak/freshrss-invidious
 * With extensions from https://github.com/cn-tools/freshrss-invidious
 * Latest version can be found at https://github.com/tunbridgep/freshrss-invidious
 *
 * @author Paul Tunbridge forking Korbak forking Kevin Papst
 */
class InvidiousExtension extends Minz_Extension
{
    /**
     * Video player width
     * @var int
     */
    protected $width = 560;
    /**
     * Video player height
     * @var int
     */
    protected $height = 315;
    /**
     * Whether we display the original feed content
     * @var bool
     */
    protected $showContent = false;
    /**
     * Invidious instance to use
     * @var string
     */
    protected $instance = 'yewtu.be';
    /**
     * The text for the YouTube link
     * @var string
     */
    protected $youtube_link_text = 'Youtube Link';
    
    /**
     * Enable globally replacing Youtube embeds
     * @var bool
     */
    protected $replace_global = false;

    /**
     * Initialize this extension
     */
    public function init()
    {
        // Make sure to not run on server without libxml
        if (!extension_loaded('xml')) {
            return;
        }
    
        $this->registerHook('entry_before_insert', array($this, 'handleInvidious'));    
        //$this->registerHook('entry_before_display', array($this, 'handleInvidious'));
        $this->registerTranslates();
    }

    /**
     * Initializes the extension configuration, if the user context is available.
     * Do not call that in your extensions init() method, it can't be used there.
     */
    public function loadConfigValues()
    {
        if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
            return;
        }

        if (FreshRSS_Context::$user_conf->in_player_width != '') {
            $this->width = FreshRSS_Context::$user_conf->in_player_width;
        }
        if (FreshRSS_Context::$user_conf->in_player_height != '') {
            $this->height = FreshRSS_Context::$user_conf->in_player_height;
        }
        if (FreshRSS_Context::$user_conf->in_show_content != '') {
            $this->showContent = (bool)FreshRSS_Context::$user_conf->in_show_content;
        }
        if (FreshRSS_Context::$user_conf->in_replace_global != '') {
            $this->replace_global = (bool)FreshRSS_Context::$user_conf->in_replace_global;
        }
        if (FreshRSS_Context::$user_conf->in_player_instance != '') {
            $this->instance = FreshRSS_Context::$user_conf->in_player_instance;
            $this->sanitizeInstanceURL();
        }
        $this->youtube_link_text = _t('ext.in_videos.youtube_link_text');
    }

    public function handleInvidious($entry)
    {
        $this->loadConfigValues();
        $link = $entry->link();
        
        //We have an invidious link (aka an invidious feed was already added manually)
        //We simply need to add the video embed, and remove the thumbnail image from the description
        if ($this->isInvidiousURL($link))
        {
            $embed_link = $this->getEmbedLink($link);
            $html = $this->getIFrameHtml($embed_link);
            $html .= '<p>'.$this->getVideoDescriptionFromFeed($entry)."</p>";
            
            if ($this->showContent) {
                $html .= $this->getNiceYoutubeLinkText($link);
            }
            
            $entry->_content($html);
        }
        
        //We have a youtube link
        //We need to embed the invidious video, update the link and fetch the description from our instance
        else if ($this->isYoutubeURL($link))
        {
            $invidious_link = $this->getInstanceLinkFromYoutubeLink($link);
            
            print_r($invidious_link);
            
            $embed_link = $this->getEmbedLink($invidious_link);
            $html = $this->getIFrameHtml($embed_link);
            $html .= '<p>'.$this->getVideoDescriptionFromInstance($invidious_link)."</p>";
            
            if ($this->showContent) {
                $html .= $this->getNiceYoutubeLinkText($link);
            }
            
            $entry->_link($invidious_link);
            $entry->_content($html);
        }
        
        //We are not a Youtube or Invidious URL, but we should still handle any youtube embeds
        else if ($this->replace_global)
        {
            $html = $entry->content();
            $html = $this->replaceYoutubeEmbeds($html);
            
            $entry->_content($html);
        }
     
        return $entry;        
    }
    
    private function replaceYoutubeEmbeds(string $html) : string
    {   
        
        libxml_use_internal_errors(true);
        $article = new DOMDocument;
        $article->validateOnParse = true;
        $article->loadHTML($html);
        libxml_use_internal_errors(false);
        
        //fix embeds
        $iframes = $article->getElementsByTagName('iframe');
        foreach ($iframes as $iframe)
        {
            $src = $iframe->getAttribute("src");
            if ($this->isYoutubeURL($src))
            {
                $src = $this->getInstanceLinkFromYoutubeLink($src);
                $iframe->setAttribute('src',$src);
            }
        }
        
        //fix links
        $links = $article->getElementsByTagName('a');
        foreach ($links as $link)
        {
            $href = $link->getAttribute("href");
            if ($this->isYoutubeURL($href))
            {
                $href = $this->getInstanceLinkFromYoutubeLink($href);
                $link->setAttribute('href',$href);
            }
        }

        return $article->saveHTML();
    }

    private function isYoutubeURL(string $url): bool
    {
        $url_info = parse_url($url);
        $hostname = $url_info['host']; //base URL, which should be invidious instance
        $hostname = str_replace("www.","",$hostname);
            
        return $hostname == 'youtube.com' || $hostname == 'youtube-nocookie.com';
    }

    //Check if the base URL of an entry is an invidious URL
    private function isInvidiousURL(string $url): bool 
    {
        $url_info = parse_url($url);
        $hostname = $url_info['host']; //base URL, which should be invidious instance
        $hostname = str_replace("www.","",$hostname);
        
        return $hostname == $this->instance || $hostname == "yewtu.be";
    }
    
    //Get the embed link for an invidious video
    private function getEmbedLink(string $invidious_url): string
    {
        $remove_watch = str_replace("watch?v=","",$invidious_url);
        return str_replace($this->instance,$this->instance."/embed",$remove_watch);
    }

    //Get a formatted "Watch on Youtube" link
    private function getNiceYoutubeLinkText(string $link)
    {
        //just in case it's originally an invidious feed, we need to make sure it's using the youtube url
        $yt_url = str_replace($this->instance,"youtube.com",$link);
        $yt_url = str_replace("yewtu.be","youtube.com",$yt_url);
    
        return '<p><a target="_blank" rel="noreferrer" href="'.$yt_url.'">'.$this->youtube_link_text.'</a></p>';
    }
    
    //Get an invidious link from our youtube link
    private function getInstanceLinkFromYoutubeLink(string $youtube_url): string
    {
        $base_url = str_replace("youtube.com",$this->instance,$youtube_url);
        $base_url = str_replace("youtube-nocookie.com",$this->instance,$base_url); 
        $watch_url = str_replace("watch?v=","",$base_url);
        return $watch_url;
    }
    
    /**
     * Returns an HTML <iframe> for a given URL for the configured width and height.
     *
     * @param string $url
     * @return string
     */
    private function getIFrameHtml($url)
    {
    
        return '<iframe 
                style="height: ' . $this->height . 'px; width: ' . $this->width . 'px;" 
                width="' . $this->width . '" 
                height="' . $this->height . '" 
                src="' . $url . '" 
                frameborder="0" 
                allowfullscreen></iframe>';
    }
    
    //removes everything but the basename from the instance URL, as well as the trailing slash
    private function sanitizeInstanceURL()
    {
        $url_info = parse_url($this->instance);
        $hostname = $url_info['host']; 
        if ($hostname != "")
            $this->instance = $hostname;
    }

    /*
     * fetch the video description
     */
     protected function getVideoDescriptionFromInstance($link)
     {            
        //Youtube delivers no textual content with it's videos - we will have to fetch it
        libxml_use_internal_errors(true);
        $instance_page = file_get_contents($link);
        $page = new DOMDocument;
        $page->validateOnParse = true;
        $page->loadHtml($instance_page);
        $desc_element = $page->getElementById('descriptionWrapper');
        libxml_use_internal_errors(false);
        
        return $desc_element->textContent;
     }

    /*
     * fetch the video description
     */
    protected function getVideoDescriptionFromFeed($entry)
    {
        libxml_use_internal_errors(true);
        $article = new DOMDocument;
        $article->validateOnParse = true;
        $article->loadHTML($entry->content());
        libxml_use_internal_errors(false);
        
        return $article->textContent;
    }

    /**
     * Saves the user settings for this extension.
     */
    public function handleConfigureAction()
    {
        $this->registerTranslates();
        $this->loadConfigValues();

        if (Minz_Request::isPost()) {
            FreshRSS_Context::$user_conf->in_player_height = (int)Minz_Request::param('in_height', '');
            FreshRSS_Context::$user_conf->in_player_width = (int)Minz_Request::param('in_width', '');
            FreshRSS_Context::$user_conf->in_show_content = (int)Minz_Request::param('in_show_content', 0);
            FreshRSS_Context::$user_conf->in_player_instance = (string)Minz_Request::param('in_instance', '');
            FreshRSS_Context::$user_conf->in_replace_global = (bool)Minz_Request::param('in_replace_global', '');
            FreshRSS_Context::$user_conf->save();
        }
    }
}
