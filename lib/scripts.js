addEventListener("DOMContentLoaded", (event) => {
 
    const lenis = new Lenis();
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
      lenis.raf(time * 1000); // Convert time from seconds to milliseconds
    });
    gsap.ticker.lagSmoothing(0);

  gsap.registerPlugin(ScrollTrigger, SplitText, ScrollSmoother, ScrambleTextPlugin, MotionPathPlugin);

  gsap.utils.toArray(".gsap-fade-in").forEach((el) => {
    gsap.from(el, {
      scrollTrigger: {
        trigger: el,
        start: "top 70%",
        end: "bottom 30%",
        scrub: true,
        toggleActions: "play none none reverse",
        //markers: true
      },
      opacity: 0,
      y: 70,
      duration: 1.2,
      ease: "expo.out"
    });
  });

  gsap.utils.toArray(".gsap-fade-in-origin").forEach((el) => {
    gsap.from(el, {
      opacity: 0,
      y: 70,
      duration: 1.2,
      ease: "expo.out"
    });
  });

  gsap.utils.toArray(".gsap-fade-in-chars").forEach((el) => {

    const split = new SplitText(el, { type: "chars" });

    gsap.from(split.chars, {
      scrollTrigger: {
        trigger: el,
        start: "top 70%",
        end: "bottom 30%",
        scrub: true,
        toggleActions: "play none none reverse",
        //markers: true
      },
      opacity: 0,
      y: 50,
      stagger: 0.03,
      duration: 1,
      ease: "power3.out"
    });

  });

  gsap.utils.toArray(".gsap-fade-in-chars-origin").forEach((el) => {

    const split = new SplitText(el, { type: "chars" });

    gsap.from(split.chars, {
      opacity: 0,
      y: 50,
      stagger: 0.03,
      duration: 1,
      ease: "power3.out"
    });

  });

  gsap.utils.toArray(".gsap-fade-in-words").forEach((el) => {

    const split = new SplitText(el, { type: "words" });

    gsap.from(split.words, {
      scrollTrigger: {
        trigger: el,
        start: "top 70%",
        end: "bottom 30%",
        scrub: true,
        toggleActions: "play none none reverse",
        //markers: true
      },
      opacity: 0,
      y: 50,
      stagger: 0.03,
      duration: 1,
      ease: "power3.out"
    });

  });

  gsap.utils.toArray(".gsap-fade-in-lines").forEach((el) => {

    const split = new SplitText(el, { type: "lines" });

    gsap.from(split.lines, {
      scrollTrigger: {
        trigger: el,
        start: "top 70%",
        end: "bottom 30%",
        scrub: true,
        toggleActions: "play none none reverse",
        //markers: true
      },
      opacity: 0,
      y: 50,
      stagger: 0.03,
      duration: 1,
      ease: "power3.out"
    });

  });

  gsap.utils.toArray(".gsap-rotate-360 img").forEach((el) => {

    gsap.from(el, {
      scrollTrigger: {
        trigger: el,
        start: "bottom bottom",
        end: "bottom 30%",
        toggleActions: "play none none reverse",
      },
      rotate: 360,
      stagger: 0.03,
      duration: 2
    });

  });

  gsap.utils.toArray(".gsap-fade-in-words-origin").forEach((el) => {

    const split = new SplitText(el, { type: "words" });

    gsap.from(split.words, {
      opacity: 0,
      y: 50,
      stagger: 0.03,
      duration: 1,
      ease: "power3.out"
    });

  });

  gsap.utils.toArray(".gsap-scramble-number").forEach((el) => {

    gsap.from(el, {duration: 1});

    //or customize things:
    gsap.from(el, {
    duration: 1, 
    scrollTrigger: {
        trigger: el,
        start: "top 100%",
        toggleActions: "play none none reverse",
        //markers: true
    },
    scrambleText: {
        text: "{original}",
        chars: "0123456789", 
        revealDelay: 0.5, 
        speed: 0.3,
    }
    });

  });

  gsap.utils.toArray(".gsap-sticky-header").forEach((el) => {

    const showAnim = gsap.from(el, { 
      yPercent: -100,
      paused: true,
      duration: 0.2
    }).progress(1);

    ScrollTrigger.create({
      start: "top top",
      end: "max",
      // markers: true,
      onUpdate: (self) => {
        if (self.direction === -1) {
          showAnim.play();
          el.classList.add("visible-header");
        } else {
          showAnim.reverse();
          el.classList.remove("visible-header");
        }
        if (self.scroll() <= window.innerHeight) {
          el.classList.remove("visible-header");
        }
      }
    });

  });

  /* Ayub */
/*
let ctx;

function createTimeline() {
  ctx && ctx.revert();

  ctx = gsap.context(() => {
    const box = document.querySelector(".imago");
    const boxStartRect = box.getBoundingClientRect();

    // All containers except the first
    const containers = gsap.utils.toArray(".imago-container:not(.initial)");

    // grab the points to animate between
    const points = containers.map((container) => {
      const marker = container.querySelector(".imago-marker") || container;
      const r = marker.getBoundingClientRect();

      return {
        x: r.left + r.width / 2 - (boxStartRect.left + boxStartRect.width / 2),
        y: r.top + r.height / 2 - (boxStartRect.top + boxStartRect.height / 2)
      };
    });

    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: ".imago-container.initial",
        start: "clamp(top center)",
        endTrigger: ".imago-trigger-end",
        end: "clamp(top center)",
        scrub: 1
      }
    });

    tl.to(".imago", {
      duration: 1,
      ease: "none",
      motionPath: {
        path: points, // array like - [{x:100, y:50}, {x:200, y:0}, {x:300, y:100}]
        curviness: 1.5 // adjust how curvy the path is, default is 1, 2 is more curvy
      }
    });
  });
}

createTimeline();
window.addEventListener("resize", createTimeline);
*/
  let sections = document.querySelectorAll(".gsap-change-background");
  sections.forEach((el, i) => {

    let bgColor = getComputedStyle(el).backgroundColor;
    el.style.backgroundColor = "transparent";
    el.style.removeProperty("background-color");
    el.classList.remove("has-custom-principal-background-color", "has-custom-accentuat-background-color", "has-custom-secundari-background-color", "has-white-background-color");
    ScrollTrigger.create({
      trigger: el,
      start: "20% 80%",
      end: "20% top",
      //markers: true,
      scrub: true,
      onToggle: self => {
        // whenever we enter a section from either direction (scrolling up or down), animate to its color
        if (self.isActive) {
          gsap.to("body", {
            backgroundColor: bgColor,
            overwrite: "auto",
          });
        // when we LEAVE the very first section scrolling in reverse -OR- when we scroll past the very last section (forward), return to the "normal" colors
        }
      }
    });
  });

});

