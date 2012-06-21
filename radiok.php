<?php
/**
 * Simple script to build a podcast feed out of Radio K's "Track of the Day" blog posts
 * Requires QueryPath
 */
require_once('QueryPath/QueryPath.php');

define('RADIOK_URL', 'http://www.radiok.org');

/**
 * URL of all blog posts tagged "track of the day"
 */
define('FEED_URL', 'http://www.radiok.org/blogs/new/tag/track%20of%20the%20day');

/**
 * Array of podcast items, which are, in turn, array
 */
$podcast = array();

$qp = htmlqp(FEED_URL);
foreach ($qp->find('.blog_post') as $post) {
    
    $pod = array();
    $h3 = $post->children('h3');
    if ($h3->size() == 0) {
        continue;
    }
    $pod['title'] = $h3->text();
    $pod['guid'] = $h3->children('a')->attr('href');
    $h3->parent();
    $post->parent();
    $h2_list = $post->children('h2');
    $song_text = $h2_list->last()->text();
    $post->parent();

    $p = $post->children('p');
    foreach ($p as $kid) {
        if ($kid->tag() == 'p') {
            $pod['description'] = $kid->text();
            break;
        }
    }
    $post->parent();

    $script = $post->children('script')->first()->text();
    $ret = preg_match('/mp3: "(.*\.mp3)"/', $script, $matches);
    if ($ret == 1) {
        $pod['audio_url'] = RADIOK_URL.$matches[1];
    }
    $post->parent();

    $pod['pub_date'] = '';
    preg_match('/(\d+)-(\d+)-(\d+)/', $pod['title'], $dateMatches);
    if (count($dateMatches) == 4) {
        $pod['pub_date'] = date('r', mktime(0,0,0,$dateMatches[1], $dateMatches[2], $dateMatches['3']));
    }

    $pod['song_artist'] = '';
    $pod['song_title'] = '';
    preg_match('/^(.*) -.*"(.*)"/', $song_text, $songMatches);
    if (count($songMatches) == 3) {
        $pod['song_artist'] = $songMatches[1];
        $pod['song_title'] = $songMatches[2];
    }
    $podcast[] = $pod;
}
$lastBuildDate = $podcast[0]['pub_date'];
?>
<?xml version="1.0" encoding="UTF-8" ?>
<?xml-stylesheet type="text/xsl" href="/standard/xsl/mpr004/podcast.xsl"?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">
  <channel>
    <title>Radio K Track of the Day</title>
    <link><?php FEED_URL ?></link>
    <description>Radio K Song of the Day</description>
    <language>en-us</language>
    <lastBuildDate><?php print($lastBuildDate); ?></lastBuildDate>
    <itunes:author>Radio K</itunes:author>

<?php 
foreach ($podcast as $item) { 
?>
        <item>
            <title><?php print(htmlspecialchars($item['song_title'])); ?></title>
            <description><?php print(htmlspecialchars($item['description'])); ?></description>
            <enclosure url="<?php print($item['audio_url']); ?>" length="3287042" type="audio/mpeg" />
            <pubDate><?php print($item['pub_date']); ?></pubDate>
            <guid isPermaLink="false"><?php print($item['guid']); ?></guid>
            <itunes:author><?php print(htmlspecialchars($item['song_artist'])); ?></itunes:author>
        </item>
<?php 
} 
?>
      </channel>
</rss>
