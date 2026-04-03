<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    dark: { 50: '#f5f5f6', 100: '#e5e5e8', 200: '#cdcdd2', 300: '#aaabb3', 400: '#81828e', 500: '#666773', 600: '#565762', 700: '#494a53', 800: '#141418', 900: '#0a0a0d', 950: '#050507' },
                    neon: { blue: '#00d4ff', purple: '#a855f7', pink: '#ec4899', cyan: '#06b6d4' },
                    accent: { DEFAULT: '#00d4ff', light: '#67e8f9', dark: '#0891b2' }
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                    display: ['Space Grotesk', 'sans-serif']
                },
                animation: {
                    'float': 'float 6s ease-in-out infinite',
                    'glow': 'glow 2s ease-in-out infinite alternate',
                    'pulse-slow': 'pulse 3s ease-in-out infinite',
                    'shimmer': 'shimmer 2s linear infinite',
                },
                keyframes: {
                    float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-20px)' } },
                    glow: { '0%': { boxShadow: '0 0 5px rgba(0,212,255,0.2), 0 0 20px rgba(0,212,255,0.1)' }, '100%': { boxShadow: '0 0 20px rgba(0,212,255,0.4), 0 0 60px rgba(0,212,255,0.2)' } },
                    shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } }
                }
            }
        }
    }
</script>
