import { createPortal } from "react-dom";
import "../assets/styles/Modal.css";

export default function Modal({ title, children, onClose }) {
  return createPortal(
    <>
      {/* Fondo oscuro con blur */}
      <div className="modal-overlay" onClick={onClose}></div>

      {/* Contenedor modal centrado */}
      <div className="modal-wrapper" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div className="modal-container">
          {/* Header con título y botón cerrar */}
          <div className="modal-header">
            <h2 id="modal-title">{title}</h2>
            <button className="modal-close-btn" onClick={onClose} aria-label="Cerrar modal">×</button>
          </div>

          {/* Contenido dinámico pasado por children */}
          <div className="modal-body">{children}</div>

          {/* Footer con botón cerrar */}
          <div className="modal-footer">
            <button className="modal-action-btn" onClick={onClose}>Cerrar</button>
          </div>
        </div>
      </div>
    </>,
    document.body
  );
}


