<?php
/*
Plugin Name: Image upload control
Version: 0.1.1
Description: Help users to keep image file names clean and descriptive.
Author: Viktor Szépe
Idea: TJNowell http://tomjn.com/
Plugin URI: https://github.com/szepeviktor/wordpress-plugin-construction
GitHub Plugin URI: https://github.com/szepeviktor/wordpress-plugin-construction
*/

/**
 * Image upload control Hungarian version
 *
 * @package image-upload-control
 */

/**
 * Stop image upload in case of a problem.
 */
final class Image_Upload_Control {

    /**
     * Hook into upload filter.
     */
    public function __construct() {

        add_filter( 'wp_handle_upload_prefilter', array( $this, 'examine_image' ) );
    }

    /**
     * Examine only images and replace underscores in file names.
     *
     * It runs in the `wp_handle_upload_prefilter` filter.
     *
     * @param array $file Details of file being uploaded.
     *
     * @return array Possibly modified file data.
     */
    public function examine_image( $file ) {

        $type = explode( '/', $file['type'] );

        if ( $file['type'] && 0 === strpos( $file['type'], 'image/' ) && function_exists( 'getimagesize' ) ) {
            $imageinfo = getimagesize( $file['tmp_name'] );
            $result = $this->image_problems( $file, $imageinfo );
            if ( is_string( $result ) ) {
                // We have a problem
                $file['error'] = $result;
            } else {
                // Don't use underscores in file names
                $file['name'] = str_replace( '_', '-', $file['name'] );
            }
        }

        return $file;
    }

    /**
     * Check for problems.
     *
     * @param array $file Details of file being uploaded.
     * @param array $imageinfo Image size data.
     *
     * @return string|null Error message or null if OK.
     */
    private function image_problems( $file, $imageinfo ) {

        // File name examples:
        // cikk-címe-mi-latható-a-képen.jpg, konferencia-beszamolo-borito.png, feri-profil-feketefehér.jpg

        // File name too short
        if ( strlen( $file['name'] ) < 14 ) {
            return 'Please rename the image to a descriptive name before uploading to make it possible to be recognized only by its name.';
        }

        // File name contains ".."
        if ( strpos( $file['name'], '..' ) ) {
            return 'Please remove multiple dots from the file name before uploading.';
        }

        $blacklist = '/'
            .'^[^0-9a-z]' // Begins with non-alpha
            .'|^.?DSC' // Camera image
            .'|^.?IMG' // Numbered image
            .'|Screen.*Shot.*[0-9]+' // Screenshot
            .'|[0-9]{2,}x[0-9]{2,}' // Size in name "100x200"
            .'/i' // Case-insensitive
        ;
        /**
         * Filters the file name blacklist regex string.
         *
         * @param string $blacklist File name blacklist regex.
         * @param string $file      The file data.
         */
        $blacklist = apply_filters( 'cmu_filename_blacklist_regex', $blacklist, $file );
        if ( 1 === preg_match( $blacklist, $file['name'] ) ) {
            return 'Please rename the image to a descriptive name before uploading, start with a letter or a digit and exclude its dimensions.';
        }

        // Cannot get image size
        if ( false === $imageinfo ) {
            return 'This is a faulty image. Please regenerate it if it is not the original image.';
        }

        /**
         * Filters the upper limit of image's pixel size.
         *
         *                          Default value is 2173600, FullHD resolution 1920 × 1080
         *                          + 100 000 as sometimes there are more rows/columns
         *                          than visible pixels depending on the format.
         * @param string $imageinfo Maximum pixel size.
         * @param string $file      The file data.
         */
        $pixel_max = apply_filters( 'cmu_pixel_max', 2173600, $imageinfo, $file );
        $pixels = $imageinfo[0] * $imageinfo[1];
        if ( $pixels > $pixel_max ) {
            return 'Please resize the image before uploading at most to FullHD (1920×1080)';
        }

        /**
         * Filters the lower limit of image's pixel size.
         *
         *                          Default value is 1024, 32 × 32 pixels.
         * @param string $imageinfo Minimum pixel size.
         * @param string $file      The file data.
         */
        $pixel_min = apply_filters( 'cmu_pixel_min', 1024, $imageinfo, $file );
        if ( $pixels < $pixel_min ) {
            return 'Please upload images with at least 32 pixels in both dimensions.';
        }

        // The image is OK.
    }
}

new Image_Upload_Control();
