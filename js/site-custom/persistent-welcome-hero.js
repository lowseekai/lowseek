(function () {
  const app = flarum.reg.get('core', 'forum/app');

  app.initializers.add(
    'iseekup-persistent-welcome-hero',
    function () {
      try {
        window.localStorage.removeItem('welcomeHidden');
      } catch (error) {
        // Ignore restricted browser storage.
      }

      if (!document.getElementById('iseekup-persistent-welcome-hero-style')) {
        const style = document.createElement('style');
        style.id = 'iseekup-persistent-welcome-hero-style';
        style.textContent = '.WelcomeHero .Hero-close { display: none !important; }';
        document.head.appendChild(style);
      }
    },
    -100
  );

  module.exports = {};
})();
