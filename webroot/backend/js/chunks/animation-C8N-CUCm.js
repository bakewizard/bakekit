function slideUp(target, duration = 500) {
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = duration + "ms";
  target.style.boxSizing = "border-box";
  target.style.height = target.offsetHeight + "px";
  target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  window.setTimeout(() => {
    target.style.display = "none";
    target.style.removeProperty("height");
    target.style.removeProperty("padding-top");
    target.style.removeProperty("padding-bottom");
    target.style.removeProperty("margin-top");
    target.style.removeProperty("margin-bottom");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-duration");
    target.style.removeProperty("transition-property");
  }, duration);
}
function slideDown(target, duration = 500) {
  target.style.removeProperty("display");
  let display = window.getComputedStyle(target).display;
  if (display === "none") display = "block";
  target.style.display = display;
  let height = target.offsetHeight;
  target.style.overflow = "hidden";
  target.style.height = 0;
  target.style.paddingTop = 0;
  target.style.paddingBottom = 0;
  target.style.marginTop = 0;
  target.style.marginBottom = 0;
  target.offsetHeight;
  target.style.boxSizing = "border-box";
  target.style.transitionProperty = "height, margin, padding";
  target.style.transitionDuration = duration + "ms";
  target.style.height = height + "px";
  target.style.removeProperty("padding-top");
  target.style.removeProperty("padding-bottom");
  target.style.removeProperty("margin-top");
  target.style.removeProperty("margin-bottom");
  window.setTimeout(() => {
    target.style.removeProperty("height");
    target.style.removeProperty("overflow");
    target.style.removeProperty("transition-duration");
    target.style.removeProperty("transition-property");
  }, duration);
}
function slideToggle(target, duration = 500) {
  if (window.getComputedStyle(target).display === "none") {
    return slideDown(target, duration);
  } else {
    return slideUp(target, duration);
  }
}
function fadeIn(element, duration, callback) {
  element.style.opacity = 0;
  element.style.display = "";
  let start = null;
  const animate = (timestamp) => {
    if (!start)
      start = timestamp;
    const progress = timestamp - start;
    element.style.opacity = progress / duration;
    if (progress < duration) {
      requestAnimationFrame(animate);
    } else {
      if (callback && typeof callback === "function") {
        callback.call(element);
      }
    }
  };
  requestAnimationFrame(animate);
}
function fadeOut(element, duration, callback) {
  let start = null;
  const animate = (timestamp) => {
    if (!start)
      start = timestamp;
    const progress = timestamp - start;
    element.style.opacity = 1 - progress / duration;
    if (progress < duration) {
      requestAnimationFrame(animate);
    } else {
      element.style.display = "none";
      if (callback && typeof callback === "function") {
        callback.call(element);
      }
    }
  };
  requestAnimationFrame(animate);
}
const animation = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  fadeIn,
  fadeOut,
  slideDown,
  slideToggle,
  slideUp
}, Symbol.toStringTag, { value: "Module" }));
export {
  animation as a,
  fadeOut as f
};
