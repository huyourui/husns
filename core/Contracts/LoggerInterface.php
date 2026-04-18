<?php
/**
 * HuSNS - 一款免费开源的社交平台
 * 
 * @author  HYR
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT
 * @声明    严禁用于违法违规用途
 */

namespace Core\Contracts;

/**
 * 日志接口
 * 
 * 定义日志记录的基本接口
 * 
 * @package Core\Contracts
 */
interface LoggerInterface
{
    /**
     * 记录 emergency 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function emergency(string $message, array $context = []): bool;

    /**
     * 记录 alert 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function alert(string $message, array $context = []): bool;

    /**
     * 记录 critical 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function critical(string $message, array $context = []): bool;

    /**
     * 记录 error 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function error(string $message, array $context = []): bool;

    /**
     * 记录 warning 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function warning(string $message, array $context = []): bool;

    /**
     * 记录 notice 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function notice(string $message, array $context = []): bool;

    /**
     * 记录 info 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function info(string $message, array $context = []): bool;

    /**
     * 记录 debug 级别日志
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function debug(string $message, array $context = []): bool;

    /**
     * 记录日志（通用方法）
     *
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public function log(string $level, string $message, array $context = []): bool;
}
