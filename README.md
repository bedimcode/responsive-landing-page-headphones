<div align="center">

# 🎧 Beats Studio — Premium Headphones Landing Page

[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![GSAP](https://img.shields.io/badge/GSAP-88CE02?style=for-the-badge&logo=greensock&logoColor=black)](https://greensock.com/gsap/)

**A high-converting, immersive e-commerce landing page for premium headphones — built with buttery-smooth GSAP animations, Tailwind CSS, Lenis scroll, and a fully functional localStorage cart.**

[Live Demo →](#) · [Report Bug →](../../issues) · [Request Feature →](../../issues)

![Hero Preview](preview.png)

</div>

---

## ✨ Key Features

| Feature | Description |
|---------|-------------|
| 🎬 **GSAP Animations** | Cinematic hero timeline, scroll-triggered reveals, parallax effects, and 3D product card tilts |
| 🛒 **Working Cart System** | Add-to-cart with localStorage persistence, quantity controls, drawer UI, and toast notifications |
| 🎨 **Color Swatches** | Interactive hero & quick-view color switching with smooth image transitions |
| ⏱️ **Countdown Timer** | Session-persisted promotional countdown that survives page refreshes |
| 📱 **Mobile-First Responsive** | Flawless experience from 320px phones to ultra-wide desktops |
| 🌊 **Lenis Smooth Scroll** | Butter-smooth scrolling synced with GSAP ScrollTrigger |
| 🔍 **Quick-View Modal** | Product details overlay with gallery-style color switching |
| 💬 **Testimonials Carousel** | Responsive slider with touch-friendly dot navigation |
| ❓ **Animated FAQ Accordion** | GSAP-powered expand/collapse with smooth height transitions |
| 📧 **Newsletter CTA** | Email capture section with inline validation |
| 📜 **Legal Pages** | Privacy Policy, Terms of Service, and Cookie Policy with consistent footer |
| ♿ **Accessibility** | `prefers-reduced-motion` support, keyboard navigation, semantic HTML |

---

## 🛠️ Tech Stack

| Technology | Purpose |
|-----------|---------|
| **HTML5** | Semantic markup, SEO-friendly structure |
| **Tailwind CSS** (CDN) | Utility-first styling with custom dark theme & neon accent colors |
| **Vanilla JavaScript** | Zero-dependency interactions and cart logic |
| **GSAP 3** + ScrollTrigger | Professional-grade scroll animations and timelines |
| **Lenis** | Smooth-scroll engine synced with GSAP ticker |
| **Remix Icons** | Lightweight icon set via CDN |
| **Google Fonts** | Inter + Space Grotesk for modern typography |

---

## 🚀 Getting Started

No build step needed — this is a pure static site.

### Option 1: Open directly

```
Just double-click index.html in your browser.
```

### Option 2: VS Code Live Server (recommended)

```bash
# 1. Clone the repo
git clone https://github.com/your-username/headphones1.duka.git
cd headphones1.duka

# 2. Open in VS Code
code .

# 3. Install "Live Server" extension, then right-click index.html → "Open with Live Server"
```

### Option 3: Any static server

```bash
# Python
python -m http.server 8080

# Node (npx)
npx serve .
```

---

## 📁 Project Structure

```
headphones1.duka/
├── index.html              # Main landing page (all sections)
├── privacy-policy.html     # Privacy Policy legal page
├── terms-of-service.html   # Terms of Service legal page
├── cookie-policy.html      # Cookie Policy legal page
├── preview.png             # Repository preview screenshot
├── LICENSE                 # MIT License
├── README.md               # This file
├── .gitignore
├── .env.example            # Environment variable template
└── assets/
    ├── css/
    │   └── styles.css      # Custom styles (Lenis, scrollbar, glassmorphism, etc.)
    ├── img/
    │   ├── favicon.png     # Site favicon
    │   ├── logo.png        # Beats logo
    │   ├── home.png        # Hero headphone image
    │   ├── case.png        # Carrying case section
    │   ├── discount.png    # Discount section visual
    │   ├── specs.png       # Specifications visual
    │   ├── product1-5.png  # Product catalog images
    │   └── sponsor1-4.png  # Sponsor / partner logos
    └── js/
        ├── main.js         # GSAP animations, Lenis, UI interactions (542 lines)
        └── cart.js         # Cart module — IIFE with localStorage (168 lines)
```

---

## 🎨 Design System

| Token | Value | Usage |
|-------|-------|-------|
| `dark-950` | `#050507` | Page background |
| `dark-800` | `#141418` | Card backgrounds |
| `neon-blue` | `#00d4ff` | Primary accent, CTAs, links |
| `neon-purple` | `#a855f7` | Gradients, secondary accent |
| `neon-pink` | `#ec4899` | Hover states, badges |
| Font: **Space Grotesk** | 400–700 | Headings, display text |
| Font: **Inter** | 300–900 | Body text, UI elements |

---

## 🌐 Deployment

This is a static site — deploy anywhere:

| Platform | How |
|----------|-----|
| **Netlify** | Drag & drop the folder, or connect GitHub repo |
| **Vercel** | `vercel --prod` or connect repo |
| **GitHub Pages** | Settings → Pages → Deploy from branch |
| **Cloudflare Pages** | Connect repo, no build command needed |

---

## 📄 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for details.

---

## 🙏 Credits

- **Original template** by [Bedimcode](https://www.youtube.com/c/Bedimcode) — responsive headphones landing page concept
- **Overhauled & rebuilt** by **NAKOLA** — complete rewrite with Tailwind CSS, GSAP 3 animations, Lenis smooth scroll, cart system, legal pages, and premium dark theme

---

<div align="center">

Made with ❤️ in Nairobi by **NAKOLA**

⭐ Star this repo if you found it useful!

</div>
