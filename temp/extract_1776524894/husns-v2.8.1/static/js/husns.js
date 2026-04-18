/**
 * HuSNS 前端优化模块
 * 
 * 功能：
 * - 图片懒加载
 * - 无限滚动
 * - 网络状态检测
 * - 离线提示
 * - 性能优化
 * 
 * @author  HYR
 * @version 2.7.0
 */

(function(window) {
    'use strict';
    
    var HuSNS = {
        version: '2.7.0',
        initialized: false,
        
        init: function() {
            if (this.initialized) return;
            this.initialized = true;
            
            this.lazyLoad.init();
            this.infiniteScroll.init();
            this.networkStatus.init();
            this.performance.init();
            this.accessibility.init();
            
            console.log('HuSNS v' + this.version + ' initialized');
        }
    };
    
    /**
     * 图片懒加载模块
     */
    HuSNS.lazyLoad = {
        options: {
            rootMargin: '50px 0px',
            threshold: 0.01,
            dataSrc: 'data-src',
            loadingClass: 'lazy-loading',
            loadedClass: 'lazy-loaded'
        },
        
        observer: null,
        
        init: function() {
            if (!('IntersectionObserver' in window)) {
                this.loadAllImages();
                return;
            }
            
            var self = this;
            
            this.observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        self.loadImage(entry.target);
                        self.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: this.options.rootMargin,
                threshold: this.options.threshold
            });
            
            this.observeImages();
            
            document.addEventListener('DOMContentLoaded', function() {
                self.observeImages();
            });
        },
        
        observeImages: function() {
            var images = document.querySelectorAll('img[' + this.options.dataSrc + ']');
            var self = this;
            
            images.forEach(function(img) {
                if (!img.classList.contains(self.options.loadedClass)) {
                    self.observer.observe(img);
                }
            });
        },
        
        loadImage: function(img) {
            var src = img.getAttribute(this.options.dataSrc);
            if (!src) return;
            
            img.classList.add(this.options.loadingClass);
            
            var self = this;
            
            img.onload = function() {
                img.classList.remove(self.options.loadingClass);
                img.classList.add(self.options.loadedClass);
                img.removeAttribute(self.options.dataSrc);
            };
            
            img.onerror = function() {
                img.classList.remove(self.options.loadingClass);
                img.src = BASE_URL + '/static/images/error.png';
            };
            
            img.src = src;
        },
        
        loadAllImages: function() {
            var images = document.querySelectorAll('img[' + this.options.dataSrc + ']');
            var self = this;
            
            images.forEach(function(img) {
                self.loadImage(img);
            });
        },
        
        refresh: function() {
            if (this.observer) {
                this.observeImages();
            } else {
                this.loadAllImages();
            }
        }
    };
    
    /**
     * 无限滚动模块
     */
    HuSNS.infiniteScroll = {
        options: {
            container: '.post-list',
            item: '.post-item',
            nextLink: '.pagination .next',
            threshold: 200,
            loadingText: '加载中...',
            endText: '没有更多内容了',
            errorText: '加载失败，点击重试'
        },
        
        container: null,
        loading: false,
        ended: false,
        nextPage: null,
        loadingEl: null,
        
        init: function() {
            this.container = document.querySelector(this.options.container);
            if (!this.container) return;
            
            var nextLink = document.querySelector(this.options.nextLink);
            if (!nextLink) return;
            
            this.nextPage = nextLink.href;
            
            this.createLoadingElement();
            this.bindEvents();
        },
        
        createLoadingElement: function() {
            this.loadingEl = document.createElement('div');
            this.loadingEl.className = 'infinite-scroll-loading';
            this.loadingEl.style.cssText = 'text-align:center;padding:20px;color:#999;';
            this.loadingEl.style.display = 'none';
            this.container.parentNode.appendChild(this.loadingEl);
        },
        
        bindEvents: function() {
            var self = this;
            
            window.addEventListener('scroll', function() {
                self.checkScroll();
            });
            
            window.addEventListener('resize', function() {
                self.checkScroll();
            });
        },
        
        checkScroll: function() {
            if (this.loading || this.ended || !this.nextPage) return;
            
            var scrollHeight = document.documentElement.scrollHeight;
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var clientHeight = document.documentElement.clientHeight;
            
            if (scrollTop + clientHeight >= scrollHeight - this.options.threshold) {
                this.loadMore();
            }
        },
        
        loadMore: function() {
            if (this.loading || this.ended || !this.nextPage) return;
            
            this.loading = true;
            this.showLoading();
            
            var self = this;
            
            fetch(this.nextPage, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.text();
            })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                
                var items = doc.querySelectorAll(self.options.item);
                var nextLink = doc.querySelector(self.options.nextLink);
                
                if (items.length > 0) {
                    items.forEach(function(item) {
                        self.container.appendChild(item);
                    });
                    
                    if (typeof window.initAllPostEvents === 'function') {
                        window.initAllPostEvents();
                    }
                    
                    HuSNS.lazyLoad.refresh();
                }
                
                if (nextLink) {
                    self.nextPage = nextLink.href;
                } else {
                    self.ended = true;
                    self.showEnd();
                }
            })
            .catch(function() {
                self.showError();
            })
            .finally(function() {
                self.loading = false;
            });
        },
        
        showLoading: function() {
            this.loadingEl.innerHTML = '<span class="loading-spinner"></span> ' + this.options.loadingText;
            this.loadingEl.style.display = 'block';
        },
        
        showEnd: function() {
            this.loadingEl.innerHTML = this.options.endText;
            this.loadingEl.style.display = 'block';
        },
        
        showError: function() {
            var self = this;
            this.loadingEl.innerHTML = '<a href="javascript:void(0)" class="retry-link">' + this.options.errorText + '</a>';
            this.loadingEl.querySelector('.retry-link').addEventListener('click', function() {
                self.loading = false;
                self.loadMore();
            });
        }
    };
    
    /**
     * 网络状态检测模块
     */
    HuSNS.networkStatus = {
        online: true,
        statusEl: null,
        
        init: function() {
            this.online = navigator.onLine;
            
            this.createStatusElement();
            this.bindEvents();
        },
        
        createStatusElement: function() {
            this.statusEl = document.createElement('div');
            this.statusEl.className = 'network-status-toast';
            this.statusEl.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:5px;font-size:14px;z-index:9999;transition:opacity 0.3s;opacity:0;pointer-events:none;';
            document.body.appendChild(this.statusEl);
        },
        
        bindEvents: function() {
            var self = this;
            
            window.addEventListener('online', function() {
                self.setOnline();
            });
            
            window.addEventListener('offline', function() {
                self.setOffline();
            });
        },
        
        setOnline: function() {
            this.online = true;
            this.showToast('网络已恢复', '#4caf50');
            
            setTimeout(function() {
                HuSNS.networkStatus.hideToast();
            }, 2000);
        },
        
        setOffline: function() {
            this.online = false;
            this.showToast('网络已断开，部分功能可能不可用', '#f44336');
        },
        
        showToast: function(message, color) {
            this.statusEl.textContent = message;
            this.statusEl.style.backgroundColor = color;
            this.statusEl.style.color = '#fff';
            this.statusEl.style.opacity = '1';
        },
        
        hideToast: function() {
            this.statusEl.style.opacity = '0';
        }
    };
    
    /**
     * 性能优化模块
     */
    HuSNS.performance = {
        init: function() {
            this.optimizeImages();
            this.optimizeScroll();
            this.preconnect();
        },
        
        optimizeImages: function() {
            var images = document.querySelectorAll('img');
            
            images.forEach(function(img) {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
                
                if (!img.hasAttribute('decoding')) {
                    img.setAttribute('decoding', 'async');
                }
            });
        },
        
        optimizeScroll: function() {
            var scrollTimeout;
            
            window.addEventListener('scroll', function() {
                if (scrollTimeout) {
                    window.cancelAnimationFrame(scrollTimeout);
                }
                
                scrollTimeout = window.requestAnimationFrame(function() {
                });
            }, { passive: true });
        },
        
        preconnect: function() {
            var links = document.querySelectorAll('a[href^="http"]');
            var domains = {};
            
            links.forEach(function(link) {
                try {
                    var url = new URL(link.href);
                    if (!domains[url.origin] && url.origin !== window.location.origin) {
                        domains[url.origin] = true;
                        
                        var preconnect = document.createElement('link');
                        preconnect.rel = 'preconnect';
                        preconnect.href = url.origin;
                        document.head.appendChild(preconnect);
                    }
                } catch (e) {}
            });
        }
    };
    
    /**
     * 无障碍访问模块
     */
    HuSNS.accessibility = {
        init: function() {
            this.enhanceKeyboardNav();
            this.addSkipLink();
            this.improveFocusVisibility();
        },
        
        enhanceKeyboardNav: function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var modals = document.querySelectorAll('.modal[style*="flex"]');
                    modals.forEach(function(modal) {
                        modal.style.display = 'none';
                    });
                    
                    var dropdowns = document.querySelectorAll('.dropdown-menu.show');
                    dropdowns.forEach(function(dropdown) {
                        dropdown.classList.remove('show');
                    });
                }
            });
        },
        
        addSkipLink: function() {
            var skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.className = 'skip-link';
            skipLink.textContent = '跳转到主要内容';
            skipLink.style.cssText = 'position:absolute;top:-40px;left:0;background:#007bff;color:#fff;padding:8px 16px;z-index:10000;transition:top 0.3s;';
            
            skipLink.addEventListener('focus', function() {
                this.style.top = '0';
            });
            
            skipLink.addEventListener('blur', function() {
                this.style.top = '-40px';
            });
            
            document.body.insertBefore(skipLink, document.body.firstChild);
        },
        
        improveFocusVisibility: function() {
            var style = document.createElement('style');
            style.textContent = ':focus-visible { outline: 2px solid #007bff; outline-offset: 2px; }';
            document.head.appendChild(style);
        }
    };
    
    /**
     * 工具函数
     */
    HuSNS.utils = {
        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },
        
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var context = this;
                var args = arguments;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },
        
        formatNumber: function(num) {
            if (num >= 10000) {
                return (num / 10000).toFixed(1) + 'w';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'k';
            }
            return num.toString();
        },
        
        formatTime: function(timestamp) {
            var now = Date.now() / 1000;
            var diff = now - timestamp;
            
            if (diff < 60) {
                return '刚刚';
            } else if (diff < 3600) {
                return Math.floor(diff / 60) + '分钟前';
            } else if (diff < 86400) {
                return Math.floor(diff / 3600) + '小时前';
            } else if (diff < 604800) {
                return Math.floor(diff / 86400) + '天前';
            } else {
                var date = new Date(timestamp * 1000);
                return date.getFullYear() + '-' + 
                       (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                       date.getDate().toString().padStart(2, '0');
            }
        },
        
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                return navigator.clipboard.writeText(text);
            }
            
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                return Promise.resolve();
            } catch (e) {
                return Promise.reject(e);
            } finally {
                document.body.removeChild(textarea);
            }
        },
        
        showToast: function(message, type) {
            type = type || 'info';
            
            var colors = {
                success: '#4caf50',
                error: '#f44336',
                warning: '#ff9800',
                info: '#2196f3'
            };
            
            var toast = document.createElement('div');
            toast.className = 'husns-toast';
            toast.textContent = message;
            toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:5px;font-size:14px;z-index:9999;background:' + (colors[type] || colors.info) + ';color:#fff;animation:fadeIn 0.3s ease;';
            
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.style.animation = 'fadeOut 0.3s ease';
                setTimeout(function() {
                    document.body.removeChild(toast);
                }, 300);
            }, 2000);
        }
    };
    
    var style = document.createElement('style');
    style.textContent = '@keyframes fadeIn{from{opacity:0;transform:translateX(-50%) translateY(-10px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}@keyframes fadeOut{from{opacity:1;transform:translateX(-50%) translateY(0)}to{opacity:0;transform:translateX(-50%) translateY(-10px)}}.loading-spinner{display:inline-block;width:16px;height:16px;border:2px solid #f3f3f3;border-top:2px solid #3498db;border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}';
    document.head.appendChild(style);
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            HuSNS.init();
        });
    } else {
        HuSNS.init();
    }
    
    window.HuSNS = HuSNS;
    
})(window);
