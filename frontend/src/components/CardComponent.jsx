import '../assets/styles/CardComponent.css'; 

function CardComponent({ card, isFlipped, onPlay }) {
  const handleClick = () => {
    console.log(`CardComponent: Double-clicked card with ID: ${card.id}, Name: ${card.nombre}`);
    if (!isFlipped && onPlay) { 
      onPlay(card.id); 
    }
  };

  return (
    <div className={`poke-card ${isFlipped ? 'poke-card-back-face' : 'poke-card-front-face'}`} onDoubleClick={handleClick}>
      {isFlipped ? (
        <div className="poke-card-inner poke-card-back">
          <div className="poke-card-attribute-back">{card.atributo || 'ATRIBUTO'}</div> 
        </div>
      ) : (
        <div className="poke-card-inner poke-card-front">
          <div className="poke-card-details">
            <h5 className="poke-card-name">{card.nombre}</h5>
            <p className="poke-card-attribute">Atributo: {card.atributo}</p>
            <p className="poke-card-attack">Ataque: {card.puntos_ataque}</p> 
          </div>
        </div>
      )}
    </div>
  );
}

export default CardComponent;



