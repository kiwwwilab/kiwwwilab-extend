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

 gsap.utils.toArray(".gsap-reveal-text-on-scroll").forEach((el) => {

    let splitChar;
    let tl;
    
    const createSplit = () => {
        splitChar && splitChar.revert();
        tl && tl.revert();
        splitChar = new SplitText(el, {
          type: "chars",
          smartWrap: true
        });

        tl = gsap
        .timeline({
          scrollTrigger: {
            trigger: el,
            start: "top 50%",
            end: "bottom 50%",
            scrub: 0.75,
            //markers: true
          }
        })
        .set(
          splitChar.chars,
          {
            color: "var(--wp--preset--color--textos)",
            stagger: 0.1
          },
          0.1
        );
    };

    createSplit();
    const debouncer = gsap.delayedCall(0.2, createSplit).pause();

    window.addEventListener("resize", () => debouncer.restart(true));

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

