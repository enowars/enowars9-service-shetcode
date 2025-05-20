import random

# Lists of components for time-travel-themed titles
adjectives = [
    "Chrono", "Temporal", "Quantum", "Retro", "Neo-", "Paradoxical", "Infinite", "Lost", "Forgotten", "Eternal",
    "Time-Warped", "Futuristic", "Prehistoric", "Anachronistic", "Dimensional"
]
nouns = [
    "Voyager", "Rift", "Chronicle", "Expedition", "Odyssey", "Portal", "Paradox", "Nexus", "Traveler", "Guardian",
    "Chronometer", "Anomaly", "Epoch", "Continuum", "Diary"
]
time_periods = [
    "Jurassic Era", "Renaissance", "Victorian Age", "Roaring Twenties", "Year 3000", "Distant Future", "Big Bang",
    "Dawn of Civilization", "Age of Dinosaurs", "Cyberpunk Metropolis", "Epoch of Enlightenment", "Stone Age"
]
actions = [
    "Escape from", "Return to", "Battle for", "Quest to", "Secrets of", "Rise of", "Fall of", "Chronicles of",
    "Mysteries of", "Journey through"
]

def generate_title():
    """
    Generate a random time-travel-themed title using one of several templates.
    """
    # Choose a template
    templates = [
        "{adj} {noun}",
        "{action} the {period}",
        "{adj} {noun}: {action} the {period}",
        "The {noun} of the {period}",
        "{action} the {adj} {noun}",
        "{adj} {noun} in the {period}",
    ]
    template = random.choice(templates)

    # Fill in placeholders
    title = template.format(
        adj=random.choice(adjectives),
        noun=random.choice(nouns),
        action=random.choice(actions),
        period=random.choice(time_periods)
    )
    return title