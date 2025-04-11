const currentUrl = window.location.origin;

function createInvitationLink(companyName) {
    const createInvitationLink =  currentUrl + "/register?company=" + btoa(companyName);
    return createInvitationLink
  }

export default createInvitationLink