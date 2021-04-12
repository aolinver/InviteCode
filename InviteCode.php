<?php
/**
 * 参考：
 * https://my.oschina.net/bravozu/blog/1827254
 * https://springboot.io/t/topic/1159
 */
class InviteCode
{
    /**
     * 随机字符串
     * 变更位置以用于不同场景
     * 同一场景勿动以保证数据正确解码
     * @var string[]
     */
    private $chars = ['G', 'F', 'W', '5', 'X', 'C', '3', 'U', '9', 'Z', 'M', '6', 'N', 'T', 'B', '7', 'Y', 'R', '2', 'H', 'S', '8', 'D', 'V', 'E', 'J', '4', 'K', 'Q', 'P', 'A', 'L'];

    /**
     * 随机字符串长度
     * @var int
     */
    private $charLength;

    /**
     * 邀请码长度
     * 6 位最大支持ID到 10773290
     * 7 位最大支持ID到 357502420
     * 8 位最大支持ID到 11452834602
     * @var int
     */
    private $codeLength = 8;

    /**
     * 随机数据（盐值）
     * @var int
     */
    private $salt = 1234561;

    /**
     * 注：$prime1 与 $chars 的长度 L 互质，可保证 ( $id * $prime1) % L 在 [0,L] 上均匀分布
     * 互质：若N个整数的最大公因数是1，则称这N个整数互质。
     *       例如8,10的最大公因数是2，不是1，因此不是整数互质。
     *       7,11,13的最大公因数是1，因此这是整数互质。
     *       5和5不互质，因为5和5的公因数有1、5。
     * @var int
     */
    private $prime1 = 3;

    /**
     * 注：$prime2 与 $codeLength 互质，可保证 ( index * $prime2) % $codeLength 在 [0，$codeLength] 上均匀分布
     * @var int
     */
    private $prime2 = 15;

    public function __construct()
    {
        $this->charLength = count($this->chars);
    }

    /**
     * ID 转 邀请码
     * @param $id
     * @return string
     */
    public function idToCode($id): string
    {
        // 补位
        $id = $id * $this->prime1 + $this->salt;

        // 将 id 转换成32进制的值
        $b[0] = $id;
        for ($i = 0; $i < $this->codeLength - 1; $i++) {
            $b[$i + 1] = $b[$i] / $this->charLength;
            // 按位扩散
            $b[$i] = ($b[$i] + $i * $b[0]) % $this->charLength;
        }
        // 改变邀请码长度需要处理此处
        $b[7] = ($b[0] + $b[1] + $b[2] + $b[3] + $b[4] + $b[5] + $b[6]) * $this->prime1 % $this->charLength;

        // 混淆
        $c = [];
        for ($i = 0; $i < $this->codeLength; $i++) {
            $c[$i] = $b[$i * $this->prime2 % $this->codeLength];
        }

        // 取出的索引转换为字符
        foreach ($c as &$c_one) {
            $c_one = $this->chars[$c_one];
        }

        // 拆分为需要的字符串
        return implode('', $c);
    }

    /**
     * 邀请码解码为 ID
     * @param $code
     * @return float|int|null
     */
    public function codeToId($code)
    {
        if (mb_strlen($code) != $this->codeLength) {
            return null;
        }

        // 将字符还原成对应数字
        $a = [];
        for ($i = 0; $i < $this->codeLength; $i++) {
            $c     = $code[$i];
            $index = $this->findIndex($c);
            if ($index == -1) {
                // 异常字符串
                return null;
            }
            $a[$i * $this->prime2 % $this->codeLength] = $index;
        }

        $b = [];
        for ($i = $this->codeLength - 2; $i >= 0; $i--) {
            $b[$i] = ($a[$i] - $a[0] * $i + $this->charLength * $i) % $this->charLength;
        }

        $result = 0;
        for ($i = $this->codeLength - 2; $i >= 0; $i--) {
            $result += $b[$i];
            $result *= ($i > 0 ? $this->charLength : 1);
        }

        return ($result - $this->salt) / $this->prime1;
    }

    /**
     * 获取 code 索引
     * @param $c
     * @return int
     */
    private function findIndex($c): int
    {
        for ($i = 0; $i < $this->charLength; $i++) {
            if ($this->chars[$i] == $c) {
                return $i;
            }
        }

        return -1;
    }
}
