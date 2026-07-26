const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { decodeEntities } = window.wp.htmlEntities;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting("xanzu_payment_data", {});

const label = decodeEntities(settings.title);

const PaymentIcon = () => {
  const iconUrl = settings.icon;

  return React.createElement("img", {
    src: iconUrl,
    alt: label,
    style: {
      maxHeight: "20px",
      width: "auto",
      marginRight: "8px",
      display: "block",
    },
    className: "xanzu-payment-method-icon",
  });
};

const Content = () => {
  return React.createElement(
    "div",
    {
      className: "xanzu-payment-method-content",
    },
    decodeEntities(settings.description)
  );
};

const Label = (props) => {
  const { PaymentMethodLabel } = props.components;

  return React.createElement(
    "div",
    {
      className: "xanzu-payment-method-label-wrapper",
      style: {
        display: "flex",
        alignItems: "center",
        gap: "8px",
      },
    },
    [
      React.createElement(PaymentIcon, { key: "icon" }),
      React.createElement(PaymentMethodLabel, {
        key: "label",
        text: label,
      }),
    ]
  );
};

registerPaymentMethod({
  name: "xanzu_payment",
  label: React.createElement(Label, null),
  content: React.createElement(Content, null),
  edit: React.createElement(Content, null),
  canMakePayment: () => {
    return true;
  },
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
});
