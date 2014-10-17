<?php

 /**
  * The Excerpt CLASS
  *
  * Author: Callum Hardy <callum.hardy@absolute-design.co.uk>
  * Version 1.0
  */

  	class Excerpt {

  		/**
  		 * The Default Arguments.
  		 *
  		 * These can be overridden by passing config arguments into the `Excerpt::get($config);` call.
  		 * 
  		 * @var array
  		 */
  		public static $default_args = array(
  			/**
  			 * @param Integer [length] 
  			 * 
  			 * The maximum length of the excerpt.
  			 */
			'length' => 150,

			/**
			 * @param String [units] 
			 * 
			 * The unit of measurement the length will use. 
  		 	 * 
  		 	 * Options: `chars`, `words`
			 */
			'units' => 'chars',

			/**
			 * @param String/Array [content] 
			 * 
			 * Here you can enter the names of one or more Advanced Custom Fields. The method will search all active ACFs on the page/post and extract the excerpt from the first matching field with content
			 *
			 * Defaults to the WP content field.
			 */
			'content' => null,

			/**
			 * @param bool [title] 
			 * 
			 * This option grabs the title of the page/post. It overrides the `content` parameter
			 *
			 */
			'use_title' => false,

			/**
			 * @param Integer [match_index] 
			 * 
			 * By default the ACF search will return the first matched field with content. Here you can alter the returned index
			 * 
			 * TODO: add `last` option and negative options top select from the end of the array
			 */
			'match_index' => 0,

			/**
			 * @param String/Bool [end] 
			 * 
			 * What to append to excerpts that are longer than the maximum length. Enter a blank string of false to append nothing.
			 * 
			 * Default: `...`
			 */
			'end' => '...',

			/**
			 * @param Bool [striptags] 
			 * 
			 * Whether to string the HTML tags from the excerpt. 
			 * 
			 * TODO: Add ability to not strip tags - very dodgy to not strip tags at the moment
			 */
			'striptags' => true,

			/**
			 * @param String/Bool [readmore] 
			 * 
			 * Displays the readmore link. 
			 * 
			 * Options: Enter `true` to display the link with the default text of 'Read More'.
			 * 			Or false to display nothing.
			 * 	  		Or enter a String to display the link while also altering the links text
			 */
			'readmore' => false,

			/**
			 * @param String/Array [readmore_class]
			 * 
			 * Add some classes to the readmore link as a String or an Array
			 */
			'readmore_class' => false,

			/**
			 * @param String [readmore_id] Add an ID to the readmore link as a String
			 */
			'readmore_id' => false,

			/**
			 * @param String [container]
			 * 
			 * Wraps the excerpt in a HTML element
			 * 
			 * Eg: `span` for a <span> tag
			 */
			'container'	=> false,

			/**
			 * @param String/Array [container_class]
			 * 
			 * Add some classes to the container
			 */
			'container_class' => false,

			/**
			 * @param String [container_id]
			 * 
			 * Add an ID to the container
			 */
			'container_id' => false,

			/**
			 * @param Bool [embed_video] 
			 * 
			 * Searches the content for video links and returns an embedded video instead of text for the excerpt
			 */
			'embed_video' => false,

			/**
			 * @param String [video_parameters]
			 * 
			 * Parameters to add to the embedded video element
			 */
			'video_parameters' => false,

			/**
			 * @param Bool [echo]
			 * 
			 * Whether the `get` method will echo the excerpt or return it
			 */
			'echo' => true,

			/**
			 * @param Integer [page_id]
			 * 
			 * Which page to search for content on
			 * 
			 * Default: Current page or current page in loop
			 */
			'page_id' => null,

			/**
			 * @param string [falsy_return]
			 * 
			 * What should the plugin return when false
			 *
			 * Options: `bool`, `null`, `empty_string`
			 */
			'falsy_return' => 'empty_string'
		);

		//	To hold the merger of any config arguments with the default arguments
		public static $args = array();

	 /**
	  * Get the Excerpt.
	  *
	  * @return String
	  */

		public static function get( $config = array() ) {

			//	Merge/Overwrite the default args with any config args
			self::$args = array_merge( self::$default_args, $config );

			//	Get the content that we will extract the excerpt from
			$excerpt = self::get_content();

			//	Video Embeds
			if( self::$args['embed_video'] )
				$video_excerpt = self::get_video( $excerpt );
			else
				$video_excerpt = false;

			//	If a video has been found, use that as the excerpt
			if( $video_excerpt ):
				$excerpt = $video_excerpt;
			// Else no video was found or we aren't looking for one 
			// look for a text excerpt
			else:
				//	Which get_ Method will be used - chars or words
				if ( method_exists( 'Excerpt', 'get_' . self::$args['units'] ) )
					$unit_method = 'get_' . self::$args['units'];
				// Fall-back to default method
				else
					$unit_method = 'get_' . self::$default_args['units'];

				//	Run chosen method
				$excerpt = self::$unit_method( $excerpt );
			endif;

			//	Add Readmore link
			if( self::$args['readmore'] )
				$excerpt .= self::get_readmore();

			//	Wrap excerpt in a container element
			if( self::$args['container'] )
				$excerpt = self::wrap_excerpt( $excerpt );

			//	After all the above malarkey do we have an excerpt?
			if( $excerpt )
				//	Echo it out Straight up?
				if( self::$args['echo'] )
					echo $excerpt;
				//	Or just return it for now
				else
					return $excerpt;
			else
				// Fail pants! 
				// Life 1 - You 0
				if( self::$args['falsy_return'] === 'null' ) {
					return null;
				} else {
					return false;
				}
		}



	 /**
	  * Get the Page/Post Content.
	  *
	  * @return String 		Content we can extract an excerpt from
	  */

		public static function get_content()
		{
			global $post;

			//	Make sure we have a post ID to target content with
			if( !is_numeric( self::$args['page_id'] ) )
				$page_id = get_the_ID();
			else
				$page_id = self::$args['page_id'];

			$excerpt = null;

			//	First check if we need to exctract from the title
			//	Any content entered in here overrides the excerpt args
			if( self::$args['use_title'] ) 
				$excerpt = html_entity_decode( get_the_title() );
			$excerpt = str_replace('0xEF 0xBF 0xBD', '', $excerpt);
			// $excerpt = preg_replace('/[&#65533;]/', '_', $string);

			//	Check for an WP excerpt field
			if(!$excerpt)
				$excerpt = get_post_field( 'post_excerpt', $page_id );



			//	If no excerpt has been found, try the WP excerpt field
			if(!$excerpt) {

				//	Get an ACFs content
				if( self::$args['content'] !== null ):

					$acf_fields = get_fields( $page_id );

					$excerpt = self::search_array_by_key( self::$args['content'], $acf_fields );

				endif;

				//	If no excerpt has been found, try the WP content field
				if(!$excerpt) {
					$excerpt = strip_shortcodes( get_post_field( 'post_content', $page_id ) );
				}				
			}

			//	Strip the HTML from the excerpt - Highly recommended
			if( $excerpt && self::$args['striptags'] ) {
				return strip_tags( $excerpt );
			} else {
				if( self::$args['falsy_return'] === 'empty_string' ) {
					return '';
				} else {
					return false;
				}
			}
				
		}



	 /**
	  * Search a multidimensional array by key.
	  * 
	  * @param  String/Array	$needle		The key(s) to search for
	  * @param  Array			$haystack	The multidimensional array
	  * @param 	Integer/Bool	$index		Which value from the array of matched results will be returned. Set to false to return the entire array of matched results
	  *
	  * @author  Callum Hardy <[callum@ed.com.au]>
	  * 
	  * @return Array/String/Bool 	The matched keys value or false if nothing found
	  */
		public static function search_array_by_key( $needle, $haystack = null, $index = 0 )
		{
			if ( !is_array($haystack) ) return false;

			if( !is_array( $needle ) )
				$needle = array( $needle );

			$result = array();

			foreach ( $haystack as $key => $value)
			{		
				$sub_result = false;

				if( in_array( $key, $needle, true ) && !empty( $value ) )
					array_push( $result, $value );
					
				if( is_array( $value ) )
					$sub_result = self::search_array_by_key( $needle, $value, false );

				if( is_array( $sub_result ) )
					$result = array_merge( $result, $sub_result );
			}

			if( empty( $result ) )
				return false;
			elseif( $index === false )
				return $result;
			else
				return $result[ $index ];
		}



	 /**
	  * Reduce the Excerpt to a maximum number of characters
	  *
	  * @return String/Bool 	The reduced excerpt or false
	  */

		public static function get_chars( $excerpt = null )
		{
			if( $excerpt == null || !is_string($excerpt) )
				return false;

			//	If null length return entire excerpt
			if( self::$args['length'] == null ) return $excerpt;

			//	Local Variables
			$length = ( is_numeric( self::$args['length'] ) )
				? self::$args['length']
				: $default_args['length'] / 3;
			$length_total = strlen( $excerpt );

			//	Reduce excerpt to Arg length
			$excerpt = substr( $excerpt, 0, $length );

			//	Append trailing characters
			if( $length <= $length_total )
				$excerpt = self::add_end( $excerpt );

			return $excerpt;
		}



	 /**
	  * Reduce the Excerpt to a maximum number of words.
	  *
	  * @return String/Bool 	The reduced excerpt or false
	  */

		public static function get_words( $excerpt = null )
		{
			if( $excerpt == null || !is_string($excerpt) ) 
				return false;

			//	If null length return entire excerpt
			if( self::$args['length'] == null ) return $excerpt;

			//	Local Variables
			$excerpt = explode (' ', $excerpt);
			$length = ( is_numeric( self::$args['length'] ) )
				? self::$args['length']
				: $default_args['length'] / 3;
			$length_total = count( $excerpt );
			
			//	Reduce Array to Arg length
			$excerpt = array_slice ( $excerpt, 0, $length - 1 );

			//	Make excerpt a sting again
			$excerpt = implode( ' ', $excerpt );

			//	Append trailing characters
			if( $length <= $length_total )
				$excerpt = self::add_end( $excerpt );

			return $excerpt;
		}



	 /**
	  * Search for any videos present in the excerpt
	  *
	  * @return String/Bool 	Returns the embedded video or false if no video found
	  */

		public static function get_video( $excerpt = null )
		{
			if( $excerpt == null || !is_string($excerpt) )
				return false;

			//	Search the excerpt for a video link
			$video_found = preg_match("/https?:\/\/(www.)?(youtube|vimeo).(\S)+/", $excerpt, $video_url );

			//	Video found
			if ( $video_found ):
				//	Get an embedded version of the video
				$video_url = wp_oembed_get( $video_url[0] );

				//	Are there any extra video parameters to add
				if( self::$args['video_parameters'] )
					return self::add_custom_video_parameters( $video_url );
				else
					return $video_url;

			//	No Video found
			else:
				return false;
			endif;
		}



	 /**
	  * Wrap the Excerpt in a HTML element and add any classes or ID passed in the config args.
	  *
	  * @return void
	  */

		public static function wrap_excerpt( $excerpt = null )
		{
			if( $excerpt == null || !is_string($excerpt) ) 
				return false;

			if( self::$args['container_class'] )

				if( is_array( self::$args['container_class'] ) )
					$container_class = implode( ' ', self::$args['container_class'] );

				elseif( is_string( self::$args['container_class'] ) )
					$container_class = self::$args['container_class'];

				else
					$container_class = null;

			else
				$container_class = null;

			if( self::$args['container_id'] && is_string( self::$args['container_id'] ) )
				$container_id = self::$args['container_id'];
			else
				$container_id = null;

			$excerpt = '<'.self::$args['container'].' id="'.$container_id.'" class="'.$container_class.'">' . $excerpt . '</'.self::$args['container'].'>';

			return $excerpt;
		}



	 /**
	  * Add the trailing characters to the excerpt.
	  *
	  * @return string
	  */

		public static function add_end( $excerpt = null )
		{
			if( $excerpt == null || !is_string($excerpt) || self::$args['end'] === false ) 
				return;

			$excerpt .= self::$args['end'];

			return $excerpt;
		}



	 /**
	  * Build the Readmore link.
	  *
	  * @return string
	  */

		public static function get_readmore() {

			//	Do we need to add a link
			if( self::$args['readmore'] === false ):
				return false;
			else:
				//	Use the default Readmore text
				if( self::$args['readmore'] === true )
					$readmore_text = 'Read More';
				//	Using customised Readmore Text
				else
					$readmore_text = self::$args['readmore'];
			endif;

			// Adding classes to the Readmore link
			if( self::$args['readmore_class'] )

				if( is_array( self::$args['readmore_class'] ) )
					$readmore_class = implode( ' ', self::$args['readmore_class'] );
				elseif( is_string( self::$args['readmore_class'] ) )
					$readmore_class = self::$args['readmore_class'];
				else
					$readmore_class = null;
			//	No classes found
			else
				$readmore_class = null;

			//	Add an ID to the Readmore link
			if( self::$args['readmore_id'] && is_string( self::$args['readmore_id'] ) )
				$readmore_id = self::$args['readmore_id'];
			//	No ID found
			else
				$readmore_id = null;

			//	Return the constructed link
			return "<a id=\"".$readmore_id."\" class=\"readmore ".$readmore_class."\" href=\"".get_permalink()."\">".$readmore_text."</a>";
		}



	 /**
	  * Adds text to the end of the `src` attribute of an embedded video
	  *
	  * @param String 	$video_embed 		The embedded video
	  * 
	  * @return String
	  */

		public static function add_custom_video_parameters( $video_embed = null ) {

			if( $video_embed === null ) return false;
				
			return preg_replace(
				"@src=(['\"])?([^'\">\s]*)@", 
				"src=$1$2".self::$args['video_parameters'], 
				$video_embed
			);
		}



	 /**
	  * Test what page we are on and return the current URL.
	  *
	  * @return string
	  */

		public static function get_page_url() {

			//	Check for front page or search page
			if( is_front_page() || is_search() ):
				return home_url();

			//	Check for blog page
			elseif ( is_home() ):
				return get_permalink( get_option('page_for_posts' ) );

			//	Category Page
			elseif(is_category()):
				$category = get_query_var('cat');
				$category = get_category($category);
				return get_category_link( $category->term_id );

			//	We are on a normal page
			else:
				return get_permalink();
			endif;
		}
  	}


