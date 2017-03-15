<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace xutl\captcha;

class CaptchaAction extends \yii\captcha\CaptchaAction
{

    /**
     * 划线
     * @param $image
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $color
     * @param int $thick
     * @return bool
     */
    public function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
    {
        /* 下面两行只在线段直角相交时好使
        imagesetthickness($image, $thick);
        return imageline($image, $x1, $y1, $x2, $y2, $color);
        */
        if ($thick == 1) {
            return imageline($image, $x1, $y1, $x2, $y2, $color);
        }
        $t = $thick / 2 - 0.5;
        if ($x1 == $x2 || $y1 == $y2) {
            return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
        }
        $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
        $a = $t / sqrt(1 + pow($k, 2));
        $points = array(
            round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
            round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
            round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
            round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
        );
        imagefilledpolygon($image, $points, 4, $color);
        return imagepolygon($image, $points, 4, $color);
    }

    /**
     * Renders the CAPTCHA image based on the code using GD library.
     * @param string $code the verification code
     * @return string image contents in PNG format.
     */
    protected function renderImageByGD($code)
    {
        $image = imagecreatetruecolor($this->width, $this->height);

        $backColor = imagecolorallocate(
            $image,
            (int) ($this->backColor % 0x1000000 / 0x10000),
            (int) ($this->backColor % 0x10000 / 0x100),
            $this->backColor % 0x100
        );
        imagefilledrectangle($image, 0, 0, $this->width - 1, $this->height - 1, $backColor);
        imagecolordeallocate($image, $backColor);

        if ($this->transparent) {
            imagecolortransparent($image, $backColor);
        }

        $foreColor = imagecolorallocate(
            $image,
            (int) ($this->foreColor % 0x1000000 / 0x10000),
            (int) ($this->foreColor % 0x10000 / 0x100),
            $this->foreColor % 0x100
        );

        $length = strlen($code);
        $box = imagettfbbox(30, 0, $this->fontFile, $code);
        $w = $box[4] - $box[0] + $this->offset * ($length - 1);
        $h = $box[1] - $box[5];
        $scale = min(($this->width - $this->padding * 2) / $w, ($this->height - $this->padding * 2) / $h);
        $x = 10;
        $y = round($this->height * 27 / 40);
        for ($i = 0; $i < $length; ++$i) {
            $fontSize = (int) (rand(26, 32) * $scale * 0.8);
            $angle = rand(-10, 10);
            $letter = $code[$i];
            $box = imagettftext($image, $fontSize, $angle, $x, $y, $foreColor, $this->fontFile, $letter);
            $x = $box[2] + $this->offset;
        }

        imagecolordeallocate($image, $foreColor);

        $fontSize = (int) (rand(26, 32) * $scale * 0.8);

        imagesetthickness($image, 3);
        $cx = ($fontSize * 2) + rand(-5, 5);
        $width = $this->width / 2.66 + rand(3, 10);
        $height = $fontSize * 2.14;
        if (rand(0, 100) % 2 == 0) {
            $start = rand(0, 66);
            $cy = $this->height / 2 - rand(10, 30);
            $cx += rand(5, 15);
        } else {
            $start = rand(180, 246);
            $cy = $this->height / 2 + rand(10, 30);
        }
        $end = $start + rand(75, 110);
        imagearc($image, $cx, $cy, $width, $height, $start, $end, $foreColor);
        if (rand(1, 75) % 2 == 0) {
            $start = rand(45, 111);
            $cy = $this->height / 2 - rand(10, 30);
        } else {
            $start = rand(200, 250);
            $cy = $this->height / 2 + rand(10, 30);
        }

        $end = $start + rand(75, 100);

        imagearc($image, $this->width * .75, $cy, $width, $height, $start, $end, $foreColor);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return ob_get_clean();
    }
}