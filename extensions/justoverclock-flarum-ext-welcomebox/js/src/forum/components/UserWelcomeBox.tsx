import Component, { ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import app from 'flarum/forum/app';
import { UserWelcomeBoxAttrs } from './types';
import AvatarEditor from 'flarum/forum/components/AvatarEditor';
import listItems from 'flarum/common/helpers/listItems';

export default class UserWelcomeBox extends Component<UserWelcomeBoxAttrs> {
  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<ComponentAttrs, this>): Mithril.Children {
    return (
      <div className="containerwb">
        <div className="backgrwb">
          <div>
            <a href={app.route.user(this.attrs.user)}>
              <div className="avatarwb">{AvatarEditor.component({ user: this.attrs.user })}</div>
            </a>
          </div>
          <div className="iconbadge">{listItems(this.attrs.user.badges().toArray())}</div>
        </div>
      </div>
    );
  }
}
