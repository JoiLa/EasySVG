<?php


    /**
     * EasySVG - Generate SVG from PHP
     * @author  Simon Tarchichi <kartsims@gmail.com>
     * @version 0.1b
     *
     * @see     https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/transform
     * @see     http://stackoverflow.com/questions/14684846/flattening-svg-matrix-transforms-in-inkscape
     * @see     http://stackoverflow.com/questions/7742148/how-to-convert-text-to-svg-paths
     */
    class EasySVG
    {

        protected $font;
        protected $svg;

        public function __construct()
        {
            // default font data
            $this->font                = new \stdClass();
            $this->font->id            = '';
            $this->font->horizAdvX     = 0;
            $this->font->unitsPerEm    = 0;
            $this->font->ascent        = 0;
            $this->font->descent       = 0;
            $this->font->glyphs        = [];
            $this->font->size          = 20;
            $this->font->color         = null;
            $this->font->lineHeight    = 1;
            $this->font->letterSpacing = 0;

            $this->clearSVG();
        }

        public function clearSVG()
        {
            $this->svg = new \SimpleXMLElement( '<svg></svg>' );
            $this->svg->addAttribute( 'version' , '1.1' );
            $this->svg->addAttribute( 'xmlns' , 'http://www.w3.org/2000/svg' );
        }

        /**
         * Function takes UTF-8 encoded string and returns unicode number for every character.
         *
         * @param  string $str
         *
         * @return string
         */
        private function _utf8ToUnicode( $str )
        {
            $unicode    = [];
            $values     = [];
            $lookingFor = 1;

            for( $i = 0; $i < strlen( $str ); $i++ ) {
                $thisValue = ord( $str[ $i ] );
                if( $thisValue < 128 ) $unicode[] = $thisValue;
                else {
                    if( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
                    $values[] = $thisValue;
                    if( count( $values ) == $lookingFor ) {
                        $number = ( $lookingFor == 3 ) ?
                            ( ( $values[ 0 ] % 16 ) * 4096 ) + ( ( $values[ 1 ] % 64 ) * 64 ) + ( $values[ 2 ] % 64 ) :
                            ( ( $values[ 0 ] % 32 ) * 64 ) + ( $values[ 1 ] % 64 );

                        $unicode[]  = $number;
                        $values     = [];
                        $lookingFor = 1;
                    }
                }
            }

            return $unicode;
        }

        /**
         * Set font params (short-hand method)
         *
         * @param string  $filepath
         * @param integer $size
         * @param string  $color
         */
        public function setFont( $filepath , $size , $color = null )
        {
            $this->setFontSVG( $filepath );
            $this->setFontSize( $size );
            if( $color ) {
                $this->setFontColor( $color );
            }
        }

        /**
         * Set font size for display
         *
         * @param int $size
         *
         * @return void
         */
        public function setFontSize( $size )
        {
            $this->font->size = $size;
        }

        /**
         * Set font color
         *
         * @param string $color
         *
         * @return void
         */
        public function setFontColor( $color )
        {
            $this->font->color = $color;
        }

        /**
         * Set the line height from default (1) to custom value
         *
         * @param  float $value
         *
         * @return void
         */
        public function setLineHeight( $value )
        {
            $this->font->lineHeight = $value;
        }

        /**
         * Set the letter spacing from default (0) to custom value
         *
         * @param  float $value
         *
         * @return void
         */
        public function setLetterSpacing( $value )
        {
            $this->font->letterSpacing = $value;
        }

        /**
         * Function takes path to SVG font (local path) and processes its xml
         * to get path representation of every character and additional
         * font parameters
         *
         * @param  string $filepath
         *
         * @return void
         */
        public function setFontSVG( $filepath )
        {
            $this->font->glyphs = [];
            $z                  = new \XMLReader();
            libxml_disable_entity_loader( false );//关闭其他占用xml文件的资源
            $z->open( $filepath );

            // move to the first <product /> node
            while( $z->read() ) {
                $name = $z->name;

                if( $z->nodeType == \XMLReader::ELEMENT ) {
                    if( $name == 'font' ) {
                        $this->font->id        = $z->getAttribute( 'id' );
                        $this->font->horizAdvX = $z->getAttribute( 'horiz-adv-x' );
                    }

                    if( $name == 'font-face' ) {
                        $this->font->unitsPerEm = $z->getAttribute( 'units-per-em' );
                        $this->font->ascent     = $z->getAttribute( 'ascent' );
                        $this->font->descent    = $z->getAttribute( 'descent' );
                    }

                    if( $name == 'glyph' ) {
                        $unicode = $z->getAttribute( 'unicode' );
                        $unicode = $this->_utf8ToUnicode( $unicode );

                        if( isset( $unicode[ 0 ] ) ) {
                            $unicode = $unicode[ 0 ];

                            $this->font->glyphs[ $unicode ]            = new \stdClass();
                            $this->font->glyphs[ $unicode ]->horizAdvX = $z->getAttribute( 'horiz-adv-x' );
                            if( empty( $this->font->glyphs[ $unicode ]->horizAdvX ) ) {
                                $this->font->glyphs[ $unicode ]->horizAdvX = $this->font->horizAdvX;
                            }
                            $this->font->glyphs[ $unicode ]->d = $z->getAttribute( 'd' );

                            // save em value for letter spacing (109 is unicode for the letter 'm')
                            if( $unicode == '109' ) {
                                $this->font->em = $this->font->glyphs[ $unicode ]->horizAdvX;
                            }
                        }
                    }
                }
            }
        }

        /**
         * Add a path to the SVG
         *
         * @param string $def
         * @param array  $attributes
         *
         * @return SimpleXMLElement
         */
        public function addPath( $def , $attributes = [] )
        {
            $path = $this->svg->addChild( 'path' );
            foreach( $attributes as $key => $value ) {
                $path->addAttribute( $key , $value );
            }
            $path->addAttribute( 'd' , $def );

            return $path;
        }

        /**
         * 创建SVG验证码
         *
         * @param array $Option
         *
         * @return array
         */
        public function CreateCaptcha( $Option = [] )
        {
            if( !$Option ) {
                $Option = [
                    'width'       => 150 ,
                    'height'      => 50 ,
                    'size'        => 4 ,
                    'ignoreChars' => '014abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ,
                    'chars'       => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ,
                    'noise'       => rand( 3 , 5 ) ,
                    'font'        => [
                        'size'  => 45 ,
                        'color' => false ,
                        //设置为false，为自动随机加载“font”文件夹下的“SVG”矢量字库
                        'path'  => false ,
                    ] ,
                ];
            }
            if( isset( $Option[ 'font' ][ 'path' ] ) && $Option[ 'font' ][ 'path' ] ) {
                $FontPathData = $Option[ 'font' ][ 'path' ];
            } else {
                $FontPathData = $this->RandomLoadFontData();
            }

            $this->setFont( $FontPathData , $Option[ 'font' ][ 'size' ] , $Option[ 'font' ][ 'color' ] );
            $this->addAttribute( 'width' , "{$Option['width']}px" );
            $this->addAttribute( 'height' , "{$Option['height']}px" );
            $outArr  = $this->RandomCaptchaText( $Option );
            $lastPos = null;
            foreach( $outArr as $k => $v ) {
                list( $textWidth , $textHeight ) = $this->textDimensions( $v );
                if( is_null( $lastPos ) ) {
                    $lastPos = ( ( $Option[ 'width' ] / $Option[ 'size' ] ) ) + rand( $textWidth + 2 , $textWidth + 3 ) * $k;
                } else {
                    $lastPos = $lastPos + rand( $textWidth + 2 , $textWidth + 3 );
                }
                $this->addText( $v , $lastPos , 'center' );
            }
            $this->addLineNoise( $Option );
            return [ join( $outArr ) , $this->asXML() ];
        }

        /**
         * 随机加载字体文件
         * @return mixed
         */
        private function RandomLoadFontData()
        {
            $rootPath = join( [ __DIR__ , 'font' ] , '/' );
            $filesArr = scandir( $rootPath );
            array_walk(
                $filesArr , function( &$v , $k , $path ) {
                if( is_int( stripos( $v , '.svg' ) ) ) {
                    $v = join( [ $path , $v ] , '/' );
                } else {
                    $v = null;
                }
            } , $rootPath );
            $filesArr = array_values( array_filter( $filesArr ) );
            return $filesArr[ rand( 0 , count( $filesArr ) - 1 ) ];
        }

        /**
         * 随机生成验证码
         *
         * @param array $Option
         *
         * @return array
         */
        private function RandomCaptchaText( $Option = [] )
        {
            $chars  = $this->stripCharsFromString( $Option[ 'chars' ] , $Option[ 'ignoreChars' ] );
            $outArr = [];
            for( $i = 0; $i < $Option[ 'size' ]; $i++ ) {
                array_push( $outArr , $chars[ rand( 0 , count( $chars ) - 1 ) ] );
            }
            return $outArr;
        }

        /**
         * 验证码 求差
         *
         * @param $String
         * @param $Chars
         *
         * @return array
         */
        private function stripCharsFromString( $String , $Chars )
        {
            $String = str_split( $String );
            $Chars  = str_split( $Chars );
            return array_values( array_diff( $String , $Chars ) );
        }

        /**
         * 加入干扰线
         */
        public function addLineNoise( $Option = [] )
        {
            if( !isset( $Option[ 'noise' ] ) ) {
                $Option[ 'noise' ] = rand( 3 , 5 );
            }
            $min = 1;
            $max = 9;
            for( $i = 0; $i < $Option[ 'noise' ]; $i++ ) {
                $start = sprintf( "%d %d" , rand( 1 , 21 ) , rand( 1 , $Option[ 'height' ] - 1 ) );
                $end   = sprintf( "%d %d" , rand( $Option[ 'width' ] - 21 , $Option[ 'width' ] - 1 ) , rand( 1 , $Option[ 'height' ] - 1 ) );
                $mid1  = sprintf( "%d %d" , rand( ( $Option[ 'width' ] / 2 ) - 21 , ( $Option[ 'width' ] / 2 ) + 21 ) , rand( 1 , $Option[ 'height' ] - 1 ) );
                $mid2  = sprintf( "%d %d" , rand( ( $Option[ 'width' ] / 2 ) - 21 , ( $Option[ 'width' ] / 2 ) + 21 ) , rand( 1 , $Option[ 'height' ] - 1 ) );
                $color = $this->RandomGreyColor( $min , $max );
                $this->addPath( "M{$start} C{$mid1},{$mid2},{$end}" , [ 'fill' => 'none' , 'stroke' => $color ] );
            }

        }

        /**
         * 随机生产颜色
         */
        private function RandomGreyColor( $Min , $Max )
        {
            $value = rand( $Min , $Max );
            return sprintf( "#%s" , dechex( $value ) . dechex( $value ) . dechex( $value ) );
        }

        /**
         * Add a text to the SVG
         *
         * @param string $def
         * @param float/string $x
         * @param float/string $y
         * @param array  $attributes
         *
         * @return SimpleXMLElement
         */
        public function addText( $text , $x = 0 , $y = 0 , $attributes = [] )
        {
            $def = $this->textDef( $text );

            if( $x === 'center' || $y === 'center' ) {
                list( $textWidth , $textHeight ) = $this->textDimensions( $text );
            }

            // center horizontally
            if( $x === 'center' ) {
                if( $this->svg[ 'width' ] === NULL ) {
                    throw new \Error( 'SVG width has to be set to center the text horizontally' );
                }
                $x = ( intval( $this->svg[ 'width' ] ) - $textWidth ) / 2;
            }

            // center vertically
            if( $y === 'center' ) {
                if( $this->svg[ 'height' ] === NULL ) {
                    throw new \Error( 'SVG height has to be set to center the text vertically' );
                }
                $y = ( intval( $this->svg[ 'height' ] ) - $textHeight ) / 2;
                $y = $this->randomFloat( $y - 11 , $y - 3 );
            }
            if( $x != 0 || $y != 0 ) {
                $def = $this->defTranslate( $def , $x , $y );
            }

            if( $this->font->color ) {
                //固定颜色
                $attributes[ 'fill' ] = $this->font->color;
            } else {
                //随机颜色
                $attributes[ 'fill' ] = $this->RandomGreyColor( rand( 1 , 9 ) , rand( 1 , 9 ) );
            }

            return $this->addPath( $def , $attributes );
        }


        /**
         * Function takes UTF-8 encoded string and size, returns xml for SVG paths representing this string.
         *
         * @param string $text UTF-8 encoded text
         *
         * @return string xml for text converted into SVG paths
         */
        public function textDef( $text )
        {
            $def = [];

            $horizAdvX = 0;
            $horizAdvY = $this->font->ascent + $this->font->descent;
            $fontSize  = floatval( $this->font->size ) / $this->font->unitsPerEm;
            $text      = $this->_utf8ToUnicode( $text );

            for( $i = 0; $i < count( $text ); $i++ ) {

                $letter = $text[ $i ];

                //ignore this glyph instead of throwing an error if the font does not define it
                if( !array_key_exists( $letter , $this->font->glyphs ) ) {
                    continue;
                }

                // line break support (10 is unicode for linebreak)
                if( $letter == 10 ) {
                    $horizAdvX = 0;
                    $horizAdvY += $this->font->lineHeight * ( $this->font->ascent + $this->font->descent );
                    continue;
                }

                // extract character definition
                $d = $this->font->glyphs[ $letter ]->d;

                // transform typo from original SVG format to straight display
                $d = $this->defScale( $d , $fontSize , -$fontSize );
                $d = $this->defTranslate( $d , $horizAdvX , $horizAdvY * $fontSize * 2 );

                $def[] = $d;

                // next letter's position
                $horizAdvX += $this->font->glyphs[ $letter ]->horizAdvX * $fontSize + $this->font->em * $this->font->letterSpacing * $fontSize;
            }
            return implode( ' ' , $def );
        }


        /**
         * Function takes UTF-8 encoded string and size, returns width and height of the whole text
         *
         * @param string $text UTF-8 encoded text
         *
         * @return array ($width, $height)
         */
        public function textDimensions( $text )
        {
            $def = [];

            $fontSize = floatval( $this->font->size ) / $this->font->unitsPerEm;
            $text     = $this->_utf8ToUnicode( $text );

            $lineWidth  = 0;
            $lineHeight = ( $this->font->ascent + $this->font->descent ) * $fontSize * 2;

            $width  = 0;
            $height = $lineHeight;

            for( $i = 0; $i < count( $text ); $i++ ) {

                $letter = $text[ $i ];

                //ignore this glyph instead of throwing an error if the font does not define it
                if( !array_key_exists( $letter , $this->font->glyphs ) ) {
                    continue;
                }

                // line break support (10 is unicode for linebreak)
                if( $letter == 10 ) {
                    $width     = $lineWidth > $width ? $lineWidth : $width;
                    $height    += $lineHeight * $this->font->lineHeight;
                    $lineWidth = 0;
                    continue;
                }

                $lineWidth += $this->font->glyphs[ $letter ]->horizAdvX * $fontSize + $this->font->em * $this->font->letterSpacing * $fontSize;
            }

            // only keep the widest line's width
            $width = $lineWidth > $width ? $lineWidth : $width;

            return [ $width , $height ];
        }


        /**
         * Function takes unicode character and returns the UTF-8 equivalent
         *
         * @param  string $str
         *
         * @return string
         */
        public function unicodeDef( $unicode )
        {

            $horizAdvY = $this->font->ascent + $this->font->descent;
            $fontSize  = floatval( $this->font->size ) / $this->font->unitsPerEm;

            // extract character definition
            $d = $this->font->glyphs[ hexdec( $unicode ) ]->d;

            // transform typo from original SVG format to straight display
            $d = $this->defScale( $d , $fontSize , -$fontSize );
            $d = $this->defTranslate( $d , 0 , $horizAdvY * $fontSize * 2 );

            return $d;
        }

        /**
         * Returns the character width, as set in the font file
         *
         * @param  string  $str
         * @param  boolean $is_unicode
         *
         * @return float
         */
        public function characterWidth( $char , $is_unicode = false )
        {
            if( $is_unicode ) {
                $letter = hexdec( $char );
            } else {
                $letter = $this->_utf8ToUnicode( $char );
            }

            if( !isset( $this->font->glyphs[ $letter ] ) )
                return NULL;

            $fontSize = floatval( $this->font->size ) / $this->font->unitsPerEm;
            return $this->font->glyphs[ $letter ]->horizAdvX * $fontSize;
        }


        /**
         * Applies a translate transformation to definition
         *
         * @param  string $def definition
         * @param  float  $x
         * @param  float  $y
         *
         * @return string
         */
        public function defTranslate( $def , $x = 0 , $y = 0 )
        {
            return $this->defApplyMatrix( $def , [ 1 , 0 , 0 , 1 , $x , $y ] );
        }

        /**
         * Applies a translate transformation to definition
         *
         * @param  string  $def   Definition
         * @param  integer $angle Rotation angle (degrees)
         * @param  integer $x     X coordinate of rotation center
         * @param  integer $y     Y coordinate of rotation center
         *
         * @return string
         */
        public function defRotate( $def , $angle , $x = 0 , $y = 0 )
        {
            if( $x == 0 && $y == 0 ) {
                $angle = deg2rad( $angle );
                return $this->defApplyMatrix( $def , [ cos( $angle ) , sin( $angle ) , -sin( $angle ) , cos( $angle ) , 0 , 0 ] );
            }

            // rotate by a given point
            $def = $this->defTranslate( $def , $x , $y );
            $def = $this->defRotate( $def , $angle );
            $def = $this->defTranslate( $def , -$x , -$y );
            return $def;
        }

        /**
         * Applies a scale transformation to definition
         *
         * @param  string  $def definition
         * @param  integer $x
         * @param  integer $y
         *
         * @return string
         */
        public function defScale( $def , $x = 1 , $y = 1 )
        {
            return $this->defApplyMatrix( $def , [ $x , 0 , 0 , $y , 0 , 0 ] );
        }

        /**
         * Calculates the new definition with the matrix applied
         *
         * @param  string $def
         * @param  array  $matrix
         *
         * @return string
         */
        public function defApplyMatrix( $def , $matrix )
        {

            // if there are several shapes in this definition, do the operation for each
            preg_match_all( '/M[^zZ]*[zZ]/' , $def , $shapes );
            $shapes = $shapes[ 0 ];
            if( count( $shapes ) > 1 ) {
                foreach( $shapes as &$shape )
                    $shape = $this->defApplyMatrix( $shape , $matrix );
                return implode( ' ' , $shapes );
            }

            preg_match_all( '/[a-zA-Z]+[^a-zA-Z]*/' , $def , $instructions );
            $instructions = $instructions[ 0 ];

            $return = '';
            foreach( $instructions as &$instruction ) {
                $i = preg_replace( '/[^a-zA-Z]*/' , '' , $instruction );
                preg_match_all( '/\-?[0-9\.]+/' , $instruction , $coords );
                $coords = $coords[ 0 ];

                if( empty( $coords ) ) {
                    continue;
                }

                $new_coords = [];
                while( count( $coords ) > 0 ) {

                    // do the matrix calculation stuff
                    list( $a , $b , $c , $d , $e , $f ) = $matrix;

                    // exception for relative instruction
                    if( preg_match( '/[a-z]/' , $i ) ) {
                        $e = 0;
                        $f = 0;
                    }

                    // convert horizontal lineto (relative)
                    if( $i == 'h' ) {
                        $i = 'l';
                        $x = floatval( array_shift( $coords ) );
                        $y = 0;

                        // add new point's coordinates
                        $current_point = [
                            $a * $x + $c * $y + $e ,
                            $b * $x + $d * $y + $f ,
                        ];
                        $new_coords    = array_merge( $new_coords , $current_point );
                    } // convert vertical lineto (relative)
                    elseif( $i == 'v' ) {
                        $i = 'l';
                        $x = 0;
                        $y = floatval( array_shift( $coords ) );

                        // add new point's coordinates
                        $current_point = [
                            $a * $x + $c * $y + $e ,
                            $b * $x + $d * $y + $f ,
                        ];
                        $new_coords    = array_merge( $new_coords , $current_point );
                    } // convert quadratic bezier curve (relative)
                    elseif( $i == 'q' ) {
                        $x = floatval( array_shift( $coords ) );
                        $y = floatval( array_shift( $coords ) );

                        // add new point's coordinates
                        $current_point = [
                            $a * $x + $c * $y + $e ,
                            $b * $x + $d * $y + $f ,
                        ];
                        $new_coords    = array_merge( $new_coords , $current_point );

                        // same for 2nd point
                        $x = floatval( array_shift( $coords ) );
                        $y = floatval( array_shift( $coords ) );

                        // add new point's coordinates
                        $current_point = [
                            $a * $x + $c * $y + $e ,
                            $b * $x + $d * $y + $f ,
                        ];
                        $new_coords    = array_merge( $new_coords , $current_point );
                    }

                    // every other commands
                    // @TODO: handle 'a,c,s' (elliptic arc curve) commands
                    // cf. http://www.w3.org/TR/SVG/paths.html#PathDataCurveCommands
                    else {
                        $x = floatval( array_shift( $coords ) );
                        $y = floatval( array_shift( $coords ) );

                        // add new point's coordinates
                        $current_point = [
                            $a * $x + $c * $y + $e ,
                            $b * $x + $d * $y + $f ,
                        ];
                        $new_coords    = array_merge( $new_coords , $current_point );
                    }


                }

                $instruction = $i . implode( ',' , $new_coords );

                // remove useless commas
                $instruction = preg_replace( '/,\-/' , '-' , $instruction );
            }

            return implode( '' , $instructions );
        }



        /**
         *
         * Short-hand methods
         *
         */


        /**
         * Return full SVG XML
         * @return string
         */
        public function asXML()
        {
            return $this->svg->asXML();
        }

        /**
         * Adds an attribute to the SVG
         *
         * @param $key
         * @param $value
         *
         * @return mixed
         */
        public function addAttribute( $key , $value )
        {
            return $this->svg->addAttribute( $key , $value );
        }

        //随机浮点数的生成
        private function randomFloat( $min = 0 , $max = 1 )
        {
            return $min + mt_rand() / mt_getrandmax() * ( $max - $min );
        }
    }
