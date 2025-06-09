<?php
class RateLimitDisplay {
    public static function getStatusMessage($rate_limiter) {
        $remaining_attempts = $rate_limiter->getRemainingAttempts();
        $wait_time = $rate_limiter->getWaitTime();
        
        if ($wait_time > 0) {
            return self::formatLockoutMessage($wait_time);
        } elseif ($remaining_attempts < RateLimiter::MAX_ATTEMPTS) {
            return self::formatRemainingAttemptsMessage($remaining_attempts);
        }
        return '';
    }
    
    public static function formatLockoutMessage($wait_time) {
        $minutes = floor($wait_time / 60);
        $seconds = $wait_time % 60;
        
        $message = '<div class="rate-limit-error">Account temporarily locked. Please try again in ';
        
        if ($minutes > 0) {
            $message .= $minutes . ' minute' . ($minutes != 1 ? 's' : '');
            if ($seconds > 0) {
                $message .= ' and ' . $seconds . ' second' . ($seconds != 1 ? 's' : '');
            }
        } else {
            $message .= $seconds . ' second' . ($seconds != 1 ? 's' : '');
        }
        
        $message .= '.</div>';
        return $message;
    }
    
    public static function formatRemainingAttemptsMessage($remaining_attempts) {
        if ($remaining_attempts === 0) {
            return '<div class="rate-limit-error">Maximum login attempts exceeded. Please try again later.</div>';
        }
        
        $message = sprintf(
            '<div class="rate-limit-warning">Invalid credentials. %d login attempt%s remaining.</div>',
            $remaining_attempts,
            $remaining_attempts != 1 ? 's' : ''
        );
        
        if ($remaining_attempts <= 2) {
            $message .= '<div class="rate-limit-info">Multiple failed attempts will result in a temporary lock.</div>';
        }
        
        return $message;
    }
    
    public static function addStyles() {
        return '
        <style>
            .rate-limit-error {
                background-color: #fee2e2;
                border: 1px solid #ef4444;
                color: #991b1b;
                padding: 12px 16px;
                border-radius: 6px;
                margin: 10px 0;
                font-size: 0.9rem;
                font-weight: 500;
                text-align: center;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            .rate-limit-warning {
                background-color: #fff7ed;
                border: 1px solid #f97316;
                color: #9a3412;
                padding: 12px 16px;
                border-radius: 6px;
                margin: 10px 0;
                font-size: 0.9rem;
                font-weight: 500;
                text-align: center;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            .rate-limit-info {
                background-color: #f0f9ff;
                border: 1px solid #0ea5e9;
                color: #075985;
                padding: 8px 12px;
                border-radius: 6px;
                margin: 5px 0;
                font-size: 0.85rem;
                text-align: center;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>';
    }
} 