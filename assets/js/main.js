// 奈飞分享系统主要JavaScript文件

// 初始化页面
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有提示工具
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 自动隐藏警告消息
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // 添加淡入动画
    document.body.classList.add('fade-in');
});

// 通用工具函数
const Utils = {
    // 复制到剪贴板
    copyToClipboard: function(text, callback) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                if (callback) callback(true);
                Utils.showToast('已复制到剪贴板', 'success');
            }).catch(function() {
                // 回退方案
                Utils.fallbackCopyToClipboard(text, callback);
            });
        } else {
            Utils.fallbackCopyToClipboard(text, callback);
        }
    },
    
    // 备用复制方法
    fallbackCopyToClipboard: function(text, callback) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = '0';
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (callback) callback(successful);
            if (successful) {
                Utils.showToast('已复制到剪贴板', 'success');
            } else {
                Utils.showToast('复制失败', 'error');
            }
        } catch (err) {
            if (callback) callback(false);
            Utils.showToast('复制失败', 'error');
        }
        
        document.body.removeChild(textArea);
    },
    
    // 显示提示消息
    showToast: function(message, type = 'info', duration = 3000) {
        const toastContainer = this.getToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // 自动移除
        setTimeout(() => {
            if (toast.parentNode) {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 500);
            }
        }, duration);
    },
    
    // 获取或创建提示消息容器
    getToastContainer: function() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
        }
        return container;
    },
    
    // 格式化日期
    formatDate: function(dateString, includeTime = true) {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };
        
        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
            options.second = '2-digit';
        }
        
        return date.toLocaleString('zh-CN', options);
    },
    
    // 计算剩余时间
    timeUntil: function(dateString) {
        const now = new Date().getTime();
        const target = new Date(dateString).getTime();
        const diff = target - now;
        
        if (diff <= 0) {
            return '已过期';
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (days > 0) {
            return `${days}天${hours}小时`;
        } else if (hours > 0) {
            return `${hours}小时${minutes}分钟`;
        } else {
            return `${minutes}分钟`;
        }
    },
    
    // 确认对话框
    confirm: function(message, callback) {
        if (confirm(message)) {
            if (callback) callback();
        }
    },
    
    // AJAX请求封装
    ajax: function(options) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        options = Object.assign(defaults, options);
        
        return fetch(options.url, {
            method: options.method,
            headers: options.headers,
            body: options.data
        }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    }
};

// 表单验证
const FormValidator = {
    // 验证邮箱
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // 验证密码强度
    validatePassword: function(password, minLength = 6) {
        return password.length >= minLength;
    },
    
    // 验证表单
    validateForm: function(formElement) {
        const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, '此字段不能为空');
                isValid = false;
            } else {
                this.clearFieldError(input);
                
                // 特殊验证
                if (input.type === 'email' && !this.validateEmail(input.value)) {
                    this.showFieldError(input, '请输入有效的邮箱地址');
                    isValid = false;
                }
                
                if (input.type === 'password' && !this.validatePassword(input.value)) {
                    this.showFieldError(input, '密码长度至少6位');
                    isValid = false;
                }
            }
        });
        
        return isValid;
    },
    
    // 显示字段错误
    showFieldError: function(field, message) {
        field.classList.add('is-invalid');
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    },
    
    // 清除字段错误
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
};

// 倒计时功能
function updateCountdowns() {
    const countdowns = document.querySelectorAll('[data-countdown]');
    countdowns.forEach(element => {
        const targetDate = element.getAttribute('data-countdown');
        const timeLeft = Utils.timeUntil(targetDate);
        element.textContent = timeLeft;
        
        if (timeLeft === '已过期') {
            element.classList.add('text-danger');
        }
    });
}

// 每秒更新倒计时
setInterval(updateCountdowns, 1000);

// 页面加载完成后初始化倒计时
document.addEventListener('DOMContentLoaded', updateCountdowns);

// 全局复制函数（供内联使用）
window.copyToClipboard = Utils.copyToClipboard;
window.showToast = Utils.showToast;