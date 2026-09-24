import Component, { ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import app from 'flarum/forum/app';

export default class GuestWelcomeBox extends Component {
  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<ComponentAttrs, this>): Mithril.Children {
    const emptyAvatarImg =
      app.forum.attribute('imgUrl') || app.forum.attribute('baseUrl') + '/assets/extensions/justoverclock-welcomebox/no-avatar.png';

    return (
      <div className="containerwb">
        <div className="backgrwbguest">
          <img className="guestavatarimg" src={emptyAvatarImg} alt="" />
          <div className="guesttext">{app.translator.trans('flarum-ext-welcomebox.forum.welcomeguest')}</div>
        </div>
      </div>
    );
  }
}
