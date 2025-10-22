document.addEventListener("DOMContentLoaded",function(){r()});function r(){const e=document.querySelector(".professional-banner");if(!e)return;e.addEventListener("keypress",function(n){(n.key==="Enter"||n.key===" ")&&(n.preventDefault(),e.click())}),document.querySelectorAll(".banner-feature-card").forEach(n=>{n.addEventListener("mouseenter",function(){this.style.transform="translateY(-5px)"}),n.addEventListener("mouseleave",function(){this.style.transform="translateY(0)"})});const t=document.querySelector(".banner-rtl-badge");t&&(t.addEventListener("mouseenter",function(){this.style.transform="translateY(-5px) rotate(3deg)"}),t.addEventListener("mouseleave",function(){this.style.transform="translateY(0) rotate(0deg)"}));const a=document.querySelector(".banner-cta-button");a&&(a.addEventListener("mouseenter",function(){this.style.animation="pulse 0.5s ease"}),a.addEventListener("animationend",function(){this.style.animation=""})),o()}function o(){console.log("Professional banner viewed"),typeof gtag<"u"&&gtag("event","banner_view",{event_category:"engagement",event_label:"professional_banner"})}if(!document.querySelector("#banner-animations")){const e=document.createElement("style");e.id="banner-animations",e.textContent=`
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    `,document.head.appendChild(e)}
